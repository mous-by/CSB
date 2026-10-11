<?php

namespace Tests\Feature\Modules;

use App\Enums\Availability;
use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\Materiel;
use App\Models\StockSync;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Synchronisation du stock (cahier §15.3) : API à jeton (empreinte seule en base, révocation), import de fichier tout ou
 * rien, quantité → disponibilité, prix et promotion, références inconnues signalées, panier limité au stock, journal.
 */
class SynchroTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    public function test_the_stock_software_updates_quantities_and_prices_through_the_api(): void
    {
        [$token, $plain] = ApiToken::issue('JÀGO DÁNAYA', null);
        $laptop = Materiel::factory()->create(['reference' => 'HP-840', 'price' => 285000, 'promo_price' => 249000, 'availability' => Availability::SurDemande]);
        $mouse = Materiel::factory()->create(['reference' => 'A4-KM720', 'price' => 12500, 'availability' => Availability::Disponible]);

        $this->assertNotSame($plain, $token->token_hash);
        $this->postJson('/api/v1/stock', ['items' => [['reference' => 'HP-840', 'quantity' => 4]]])->assertUnauthorized();
        $this->withToken('csb_faux')->postJson('/api/v1/stock', ['items' => [['reference' => 'HP-840', 'quantity' => 4]]])->assertUnauthorized();

        $this->withToken($plain)->getJson('/api/v1/stock')->assertOk()->assertJsonFragment(['reference' => 'HP-840']);
        $this->withToken($plain)->postJson('/api/v1/stock', ['items' => [
            ['reference' => 'hp-840', 'quantity' => 4, 'price' => 240000],
            ['reference' => 'A4-KM720', 'quantity' => 0],
            ['reference' => 'INCONNU-1', 'quantity' => 2],
        ]])->assertOk()->assertJson(['received' => 3, 'matched' => 2, 'updated' => 2, 'unknown_references' => ['INCONNU-1']]);

        $laptop->refresh();
        $this->assertSame(4, $laptop->stock_quantity);
        $this->assertSame(Availability::Disponible, $laptop->availability);
        $this->assertSame(240000, $laptop->price);
        $this->assertNull($laptop->promo_price, 'Une promotion devenue supérieure au nouveau prix est retirée.');
        $this->assertSame(Availability::Indisponible, $mouse->fresh()->availability);
        $this->assertNotNull($token->fresh()->last_used_at);
        $this->assertTrue(ActivityLog::where('action', 'synchro.applied')->where('channel', 'api')->exists());

        // Tout ou rien : une quantité invalide refuse l'envoi entier.
        $this->withToken($plain)->postJson('/api/v1/stock', ['items' => [['reference' => 'HP-840', 'quantity' => 9], ['reference' => 'A4-KM720', 'quantity' => -1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.1.quantity');
        $this->assertSame(4, $laptop->fresh()->stock_quantity);

        $token->update(['revoked_at' => now()]);
        $this->withToken($plain)->postJson('/api/v1/stock', ['items' => [['reference' => 'HP-840', 'quantity' => 1]]])->assertUnauthorized();
    }

    public function test_the_cart_is_limited_to_the_synchronised_stock(): void
    {
        $materiel = Materiel::factory()->create(['reference' => 'IMP-T', 'price' => 70000, 'stock_quantity' => 2, 'is_published' => true]);
        $empty = Materiel::factory()->create(['reference' => 'IMP-X', 'price' => 70000, 'stock_quantity' => 0, 'is_published' => true]);

        $this->postJson("/panier/{$materiel->id}", ['quantity' => 5])->assertOk()->assertJson(['count' => 2]);
        $this->postJson("/panier/{$empty->id}")->assertNotFound();
        $this->get("/materiel/{$materiel->slug}")->assertSee('En stock : 2 disponible(s)');
    }

    public function test_the_gestionnaire_imports_a_stock_file_and_line_errors_are_reported(): void
    {
        $this->syncPermissions();
        $materiel = Materiel::factory()->create(['reference' => 'HP-450', 'price' => 300000]);
        $user = User::factory()->gestionnaire()->create();

        $this->actingAs($user)->get('/admin/synchro')->assertOk()->assertSee('IMPORTER UN FICHIER DE STOCK')->assertDontSee('JETONS DE L\'API</h6></div>', false);
        $this->get('/admin/synchro/modele')->assertOk()->assertDownload('modele-stock-csb.xlsx');

        $csv = UploadedFile::fake()->createWithContent('stock.csv', "Prix;Référence;Quantité\n310 000;HP-450;7\n;AUTRE;3\n");
        $this->post('/admin/synchro/import', ['file' => $csv])->assertRedirect('/admin/synchro')->assertSessionHas('status');
        $this->assertSame(7, $materiel->fresh()->stock_quantity);
        $this->assertSame(310000, $materiel->fresh()->price);
        $this->assertSame(['AUTRE'], StockSync::sole()->unknown_references);

        $bad = UploadedFile::fake()->createWithContent('stock.csv', "Référence;Quantité\nHP-450;beaucoup\n");
        $this->post('/admin/synchro/import', ['file' => $bad])->assertSessionHasErrors('file');
        $this->assertSame(7, $materiel->fresh()->stock_quantity);

        $this->post('/admin/synchro/jetons', ['name' => 'Test'])->assertForbidden();
    }

    public function test_the_administrateur_creates_and_revokes_tokens(): void
    {
        $this->syncPermissions();
        $this->actingAs(User::factory()->administrateur()->create());

        $this->post('/admin/synchro/jetons', ['name' => 'Logiciel de stock'])->assertRedirect('/admin/synchro')->assertSessionHas('new_token');
        $token = ApiToken::sole();
        $this->assertStringStartsWith('csb_', session('new_token'));
        $this->assertSame(hash('sha256', session('new_token')), $token->token_hash);

        $this->delete("/admin/synchro/jetons/{$token->id}")->assertRedirect();
        $this->assertNotNull($token->fresh()->revoked_at);
        $this->actingAs(User::factory()->commercial()->create())->get('/admin/synchro')->assertForbidden();
    }

    public function test_a_reference_is_unique_and_normalised_in_the_product_form(): void
    {
        $this->syncPermissions();
        Materiel::factory()->create(['reference' => 'HP-450']);
        $payload = ['designation' => 'HP 450', 'category_id' => Materiel::first()->category_id, 'condition' => 'neuf', 'availability' => 'disponible', 'sort_order' => 0];

        $this->actingAs(User::factory()->gestionnaire()->create())->post('/admin/materiels', $payload + ['reference' => 'hp-450'])->assertSessionHasErrors('reference');
        $this->post('/admin/materiels', $payload + ['reference' => ' hp-450-g9 '])->assertRedirect('/admin/materiels');
        $this->assertTrue(Materiel::where('reference', 'HP-450-G9')->exists());
    }
}
