<?php

namespace Tests\Feature\Modules;

use App\Enums\CommissionStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Commission;
use App\Models\Demande;
use App\Models\Materiel;
use App\Models\Partenaire;
use App\Models\PartenaireVisite;
use App\Models\User;
use App\Services\Partenaires\PartnerTracker;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Partenaires de diffusion (demande de CSB, 2026-10-12) : lien personnel et attribution, code promo, commissions à taux
 * réglables (automatiques sur les commandes payées, saisies pour les logiciels et le sur mesure), paiement, rôle
 * Communication (son espace, le kit) et kit de publication.
 */
class PartenaireTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('public');
        RateLimiter::clear('demandes-min|127.0.0.1');
        $this->syncPermissions();
    }

    private function partner(array $data = []): Partenaire
    {
        return Partenaire::create($data + [
            'name' => 'Hamara Kida', 'ref_code' => 'hamara', 'promo_code' => 'HAMARA10', 'promo_discount' => 10,
            'rate_materiel' => 5, 'rate_logiciel' => 10, 'rate_sur_mesure' => 7.5, 'cookie_days' => 30, 'is_active' => true,
        ]);
    }

    private function human(): array
    {
        return [SpamGuard::TOKEN => Crypt::encryptString((string) now()->subSeconds(20)->getTimestamp()), 'consent' => '1'];
    }

    private function order(array $extra = []): Commande
    {
        $materiel = Materiel::factory()->create(['price' => 200000, 'is_published' => true]);
        $this->post("/panier/{$materiel->id}", ['quantity' => 2]);
        $this->post('/commande', $extra + ['name' => 'Awa Traoré', 'phone' => '70 11 22 33', 'delivery_method' => 'retrait', 'payment_method' => 'wave'] + $this->human())->assertRedirect();

        return Commande::latest('id')->firstOrFail();
    }

    public function test_the_administrateur_creates_a_partner_with_configurable_rates(): void
    {
        $this->actingAs(User::factory()->administrateur()->create());
        $account = User::factory()->communication()->create();

        $this->post('/admin/partenaires', ['_form' => 'partenaire', 'name' => 'Hamara', 'ref_code' => 'Pas Bon!', 'promo_code' => 'ok', 'rate_materiel' => 150, 'rate_logiciel' => 0, 'rate_sur_mesure' => 0, 'cookie_days' => 30])
            ->assertSessionHasErrors(['promo_discount', 'rate_materiel']);

        $this->post('/admin/partenaires', [
            '_form' => 'partenaire', 'name' => 'Hamara Kida', 'ref_code' => 'Hamara Kida', 'promo_code' => 'hamara10', 'promo_discount' => 10,
            'rate_materiel' => 5, 'rate_logiciel' => 12.5, 'rate_sur_mesure' => 8, 'cookie_days' => 45, 'user_id' => $account->id, 'is_active' => '1',
        ])->assertRedirect();

        $partenaire = Partenaire::sole();
        $this->assertSame('hamara-kida', $partenaire->ref_code);
        $this->assertSame('HAMARA10', $partenaire->promo_code);
        $this->assertSame(12.5, $partenaire->rate_logiciel);
        $this->assertSame($account->id, $partenaire->user_id);
        $this->get("/admin/partenaires/{$partenaire->id}")->assertOk()->assertSee('?ref=hamara-kida');
        $this->get('/admin/partenaires')->assertOk()->assertSee('Hamara Kida');

        $this->put("/admin/partenaires/{$partenaire->id}", ['_form' => 'partenaire', 'partenaire_id' => $partenaire->id, 'name' => 'Hamara Kida', 'ref_code' => 'hamara-kida', 'rate_materiel' => 6, 'rate_logiciel' => 12.5, 'rate_sur_mesure' => 8, 'cookie_days' => 45, 'is_active' => '1'])->assertRedirect();
        $this->assertSame(6.0, $partenaire->fresh()->rate_materiel);
    }

    public function test_a_visitor_from_the_partner_link_is_attributed_for_requests_accounts_and_orders(): void
    {
        $partenaire = $this->partner();

        $this->get('/?ref=hamara')->assertOk()->assertCookie(PartnerTracker::COOKIE);
        $this->get('/materiel?ref=hamara');
        $this->assertSame(1, (int) $partenaire->visites()->sum('visits'), 'Une visite par jour et par visiteur.');
        $this->get('/?ref=inconnu')->assertOk();
        $this->assertSame(1, (int) PartenaireVisite::sum('visits'), 'Un code inconnu ne compte rien.');

        // Le navigateur renvoie le cookie posé par le lien (le client de test ne le garde pas seul).
        $this->withCookie(PartnerTracker::COOKIE, 'hamara');
        $this->post('/contact', ['name' => 'Moussa', 'phone' => '70 00 00 01', 'subject' => 'Info', 'message' => 'Je voudrais KalanNet pour mon école.'] + $this->human());
        $this->assertSame($partenaire->id, Demande::sole()->partenaire_id);

        $this->post('/espace-client/inscription', ['name' => 'Fanta', 'email' => 'fanta@exemple.ml', 'password' => '1234', 'password_confirmation' => '1234'] + $this->human());
        $this->assertSame($partenaire->id, Client::sole()->partenaire_id);

        $commande = $this->order();
        $this->assertSame($partenaire->id, $commande->partenaire_id);
        $this->assertSame(0, $commande->discount_amount, 'Le lien attribue la vente, seul le code promo donne une remise.');
    }

    public function test_the_promo_code_gives_a_discount_and_attributes_the_order(): void
    {
        $partenaire = $this->partner();
        $materiel = Materiel::factory()->create(['price' => 100000, 'is_published' => true]);
        $this->post("/panier/{$materiel->id}");

        $this->postJson('/commande/code-promo', ['code' => 'FAUX'])->assertStatus(422);
        $this->postJson('/commande/code-promo', ['code' => 'hamara10'])->assertOk()->assertJson(['valid' => true, 'discount' => '10 000 FCFA', 'total' => '90 000 FCFA']);
        $this->post('/commande', ['name' => 'Awa', 'phone' => '70 11 22 33', 'delivery_method' => 'retrait', 'payment_method' => 'especes', 'promo_code' => 'FAUX'] + $this->human())->assertSessionHasErrors('promo_code');

        $commande = $this->order(['promo_code' => 'hamara10']);
        $this->assertSame($partenaire->id, $commande->partenaire_id);
        $this->assertSame('HAMARA10', $commande->promo_code);
        $this->assertSame(500000, $commande->subtotal, 'Panier : 100 000 + 2 × 200 000.');
        $this->assertSame(50000, $commande->discount_amount);
        $this->assertSame(450000, $commande->total);

        $partenaire->update(['promo_ends_on' => today()->subDay()]);
        $this->postJson('/commande/code-promo', ['code' => 'HAMARA10'])->assertStatus(422);
    }

    public function test_a_paid_order_creates_the_commission_and_a_cancelled_one_cancels_it(): void
    {
        $partenaire = $this->partner();
        $this->withCookie(PartnerTracker::COOKIE, 'hamara');
        $commande = $this->order();
        $agent = User::factory()->commercial()->create();

        $this->actingAs($agent)->put("/admin/commandes/{$commande->id}/statut", ['status' => 'payee', 'payment_reference' => 'WAVE-1'])->assertRedirect();
        $commission = Commission::sole();
        $this->assertSame(400000, $commission->base_amount);
        $this->assertSame(5.0, $commission->rate);
        $this->assertSame(20000, $commission->amount);
        $this->assertSame(CommissionStatus::APayer, $commission->status);

        // Passer ensuite « Livrée » ne crée pas de seconde commission.
        $this->put("/admin/commandes/{$commande->id}/statut", ['status' => 'livree'])->assertRedirect();
        $this->assertSame(1, Commission::count());

        $this->put("/admin/commandes/{$commande->id}/statut", ['status' => 'annulee', 'cancel_reason' => 'Retour du client'])->assertRedirect();
        $this->assertSame(CommissionStatus::Annulee, $commission->fresh()->status);
    }

    public function test_software_sales_are_recorded_from_the_request_and_paid_in_a_batch(): void
    {
        $partenaire = $this->partner();
        $demande = Demande::factory()->create(['partenaire_id' => $partenaire->id]);
        $admin = User::factory()->administrateur()->create();
        $this->actingAs($admin);

        $this->get("/admin/demandes/{$demande->id}")->assertOk()->assertSee('VENUE D\'UN PARTENAIRE', false);
        $this->post("/admin/partenaires/{$partenaire->id}/commissions", ['kind' => 'logiciel', 'base_amount' => 300000, 'label' => 'Licence KalanNet', 'demande_id' => $demande->id])->assertRedirect();
        $this->post("/admin/partenaires/{$partenaire->id}/commissions", ['kind' => 'sur_mesure', 'base_amount' => 1000000, 'label' => 'Site web'])->assertRedirect();
        $this->assertSame([30000, 75000], Commission::orderBy('id')->pluck('amount')->all());

        $partenaire->update(['rate_materiel' => 0]);
        $this->post("/admin/partenaires/{$partenaire->id}/commissions", ['kind' => 'materiel', 'base_amount' => 100000, 'label' => 'Vente'])->assertSessionHas('error');

        $ids = Commission::pluck('id')->all();
        $this->post("/admin/partenaires/{$partenaire->id}/paiement", ['ids' => $ids])->assertSessionHasErrors('payment_reference');
        $this->post("/admin/partenaires/{$partenaire->id}/paiement", ['ids' => [$ids[0]], 'payment_reference' => 'OM-778899'])->assertSessionHas('status');
        $this->assertSame(CommissionStatus::Payee, Commission::find($ids[0])->status);
        $this->assertSame('OM-778899', Commission::find($ids[0])->payment_reference);
        $this->assertSame(CommissionStatus::APayer, Commission::find($ids[1])->status);
        $this->assertTrue(ActivityLog::where('action', 'partenaires.paid')->exists());

        $this->put('/admin/commissions/'.$ids[1].'/annulation', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->put('/admin/commissions/'.$ids[1].'/annulation', ['reason' => 'Vente annulée'])->assertRedirect();
        $this->assertSame(CommissionStatus::Annulee, Commission::find($ids[1])->status);
    }

    public function test_the_communication_role_sees_its_space_and_kit_but_not_the_business_data(): void
    {
        $user = User::factory()->communication()->create();
        $partenaire = $this->partner(['user_id' => $user->id]);
        $materiel = Materiel::factory()->create(['designation' => 'HP ProBook 450', 'price' => 300000, 'promo_price' => 270000, 'is_published' => true]);
        $this->actingAs($user);

        $this->get('/admin')->assertOk()->assertSee('Mes résultats de partenaire')->assertSee('Kit de publication');
        $this->get('/admin/mon-espace-partenaire')->assertOk()->assertSee($partenaire->link(), false)->assertSee('HAMARA10');
        $this->get('/admin/kit')->assertOk()->assertSee('HP ProBook 450');
        $this->get("/admin/kit/materiel/{$materiel->id}")->assertOk()
            ->assertSee('ref=hamara', false)->assertSee('Code promo HAMARA10')->assertSee('270 000 FCFA');
        $image = $this->get("/admin/kit/materiel/{$materiel->id}/visuel/carre");
        $image->assertOk();
        $this->assertSame('image/png', $image->headers->get('Content-Type'));
        $this->get("/admin/kit/materiel/{$materiel->id}/visuel/story?download=1")->assertOk()->assertDownload();

        foreach (['/admin/partenaires', '/admin/commandes', '/admin/clients', '/admin/demandes'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->assertTrue($user->can('articles.create'));
        $this->assertFalse($user->can('articles.publish'), 'Publier une actualité est une option à accorder.');
    }
}
