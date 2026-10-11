<?php

namespace Tests\Feature\Modules;

use App\Enums\Availability;
use App\Enums\CommandeStatus;
use App\Mail\CommandeForClient;
use App\Mail\CommandeForTeam;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Materiel;
use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Boutique (cahier §15.3) : panier en session (AJAX), seuls les produits avec un prix se commandent, prix promotionnel
 * appliqué et figé, commande numérotée, page de suivi secrète, traitement par l'équipe, permissions.
 */
class BoutiqueTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private function antiSpam(): array
    {
        return [SpamGuard::TOKEN => Crypt::encryptString((string) now()->subMinute()->getTimestamp())];
    }

    private function orderPayload(array $data = []): array
    {
        return $data + ['name' => 'Awa Traoré', 'phone' => '70 11 22 33', 'email' => 'awa@exemple.ml', 'delivery_method' => 'retrait', 'payment_method' => 'orange_money', 'consent' => '1'] + $this->antiSpam();
    }

    public function test_a_visitor_fills_the_cart_in_ajax_and_places_an_order_at_the_promotional_price(): void
    {
        Mail::fake();
        $laptop = Materiel::factory()->create(['price' => 285000, 'promo_price' => 249000, 'promo_ends_on' => today()->addWeek(), 'is_published' => true]);
        $mouse = Materiel::factory()->create(['price' => 12500, 'is_published' => true]);

        $this->postJson("/panier/{$laptop->id}")->assertOk()->assertJson(['count' => 1]);
        $this->postJson("/panier/{$mouse->id}", ['quantity' => 2])->assertOk()->assertJson(['count' => 3, 'total' => '274 000 FCFA']);
        $this->patchJson("/panier/{$mouse->id}", ['quantity' => 3])->assertOk()->assertJson(['count' => 4]);
        $this->get('/panier')->assertOk()->assertSee($laptop->designation)->assertSee('286 500 FCFA');
        $this->get('/')->assertSee('data-cart-count', false);

        $this->get('/commande')->assertOk()->assertSee('Valider ma commande');
        $response = $this->post('/commande', $this->orderPayload());

        $commande = Commande::with('lignes')->sole();
        $response->assertRedirect("/commande/{$commande->number}/{$commande->access_token}");
        $this->assertSame('CMD-'.now()->year.'-0001', $commande->number);
        $this->assertSame(286500, $commande->total);
        $this->assertSame('+22370112233', $commande->phone);
        $this->assertSame(249000, $commande->lignes->firstWhere('materiel_id', $laptop->id)->unit_price);
        $this->assertSame(285000, $commande->lignes->firstWhere('materiel_id', $laptop->id)->normal_price);
        $this->assertTrue(ActivityLog::where('action', 'commandes.placed')->exists());
        Mail::assertSent(CommandeForTeam::class);
        Mail::assertSent(CommandeForClient::class, fn ($m) => $m->hasTo('awa@exemple.ml'));

        $this->get('/panier')->assertSee('Votre panier est vide');
        $this->get("/commande/{$commande->number}/{$commande->access_token}")->assertOk()->assertSee('286 500 FCFA')->assertSee('Orange Money');
        $this->get("/commande/{$commande->number}/".str_repeat('a', 48))->assertNotFound();

        // Le prix change après la commande : la commande garde le sien.
        $laptop->update(['promo_price' => null]);
        $this->assertSame(286500, $commande->fresh()->total);
    }

    public function test_only_published_products_with_a_price_can_be_ordered(): void
    {
        $noPrice = Materiel::factory()->create(['price' => null, 'is_published' => true]);
        $unavailable = Materiel::factory()->create(['price' => 1000, 'availability' => Availability::Indisponible, 'is_published' => true]);
        $hidden = Materiel::factory()->create(['price' => 1000, 'is_published' => false]);

        foreach ([$noPrice, $unavailable, $hidden] as $materiel) {
            $this->postJson("/panier/{$materiel->id}")->assertNotFound();
        }
        $this->get("/materiel/{$noPrice->slug}")->assertDontSee('Ajouter au panier');
        $this->get('/commande')->assertRedirect('/panier');
    }

    public function test_a_product_unpublished_after_being_added_disappears_from_the_cart(): void
    {
        $materiel = Materiel::factory()->create(['price' => 50000, 'is_published' => true]);
        $this->post("/panier/{$materiel->id}");
        $materiel->update(['is_published' => false]);

        $this->get('/panier')->assertSee('Votre panier est vide');
    }

    public function test_validation_and_spam_protection_of_the_order_form(): void
    {
        Mail::fake();
        $materiel = Materiel::factory()->create(['price' => 50000, 'is_published' => true]);
        $this->post("/panier/{$materiel->id}");

        $this->post('/commande', ['name' => '', 'phone' => '12', 'delivery_method' => 'livraison', 'payment_method' => 'bitcoin'] + $this->antiSpam())
            ->assertSessionHasErrors(['name', 'phone', 'address', 'payment_method', 'consent']);
        $this->post('/commande', $this->orderPayload([SpamGuard::HONEYPOT => 'http://spam']))->assertRedirect('/');
        $this->assertSame(0, Commande::count());
        Mail::assertNothingSent();
    }

    public function test_a_logged_in_client_finds_his_orders_in_his_area(): void
    {
        Mail::fake();
        $client = Client::factory()->create();
        $materiel = Materiel::factory()->create(['price' => 50000, 'is_published' => true]);

        $this->actingAs($client, 'client')->post("/panier/{$materiel->id}");
        $this->get('/commande')->assertSee('value="'.$client->email.'"', false);
        $this->post('/commande', $this->orderPayload(['name' => $client->name]));

        $commande = Commande::sole();
        $this->assertSame($client->id, $commande->client_id);
        $this->get('/espace-client/commandes')->assertOk()->assertSee($commande->number);
        $this->get('/espace-client')->assertSee('Commandes');
    }

    public function test_the_team_confirms_records_the_payment_and_cancels_with_a_reason(): void
    {
        Mail::fake();
        $this->syncPermissions();
        $materiel = Materiel::factory()->create(['price' => 50000, 'is_published' => true]);
        $this->post("/panier/{$materiel->id}");
        $this->post('/commande', $this->orderPayload());
        $commande = Commande::sole();
        $agent = User::factory()->commercial()->create();

        $this->actingAs($agent)->get('/admin/commandes')->assertOk()->assertSee($commande->number);
        $this->get('/admin')->assertSee('Commandes');
        $this->get("/admin/commandes/{$commande->id}")->assertOk()->assertSee('50 000 FCFA');

        $this->put("/admin/commandes/{$commande->id}/statut", ['status' => 'payee', 'payment_reference' => 'OM123456'])->assertRedirect();
        $commande->refresh();
        $this->assertSame(CommandeStatus::Payee, $commande->status);
        $this->assertSame('OM123456', $commande->payment_reference);
        $this->assertNotNull($commande->paid_at);
        $this->assertSame($agent->id, $commande->handled_by);
        $this->assertTrue(ActivityLog::where('action', 'commandes.status_changed')->exists());
        Mail::assertSent(CommandeForClient::class, 2);

        $this->put("/admin/commandes/{$commande->id}/statut", ['status' => 'annulee'])->assertSessionHasErrors('cancel_reason');
        $this->put("/admin/commandes/{$commande->id}/statut", ['status' => 'annulee', 'cancel_reason' => 'Rupture chez le fournisseur'])->assertRedirect();
        $this->assertSame(CommandeStatus::Annulee, $commande->fresh()->status);

        $this->get('/admin/commandes/export')->assertOk()->assertDownload();
        $this->delete("/admin/commandes/{$commande->id}")->assertForbidden();
        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin/commandes')->assertForbidden();
    }
}
