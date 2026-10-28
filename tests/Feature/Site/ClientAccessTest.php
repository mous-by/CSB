<?php

namespace Tests\Feature\Site;

use App\Models\Client;
use App\Models\Materiel;
use App\Models\Media;
use App\Models\Realisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accès à l'espace client pour un public peu habitué : bouton « Mon compte client » libellé dans l'en-tête, et
 * réalisations et matériels de CSB qui défilent derrière les pages de connexion, d'inscription et du conseiller.
 */
class ClientAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_header_shows_an_explicit_account_button(): void
    {
        $this->get('/')->assertOk()->assertSee('Mon compte<span class="nav-account-extra"> client</span>', false)->assertSee('Se connecter / S&#039;inscrire', false);

        $client = Client::factory()->create(['name' => 'Moussa Diarra']);
        $this->actingAs($client, 'client')->get('/')->assertOk()->assertSee('Bonjour Moussa');
    }

    public function test_projects_and_products_scroll_behind_the_access_pages_and_the_advisor(): void
    {
        $this->get('/espace-client/connexion')->assertOk()->assertDontSee('auth-showcase-row', false);

        Realisation::factory()->create(['title' => 'KalanNet', 'cover_media_id' => Media::factory()->create()->id]);
        Realisation::factory()->draft()->create(['title' => 'Projet Secret', 'cover_media_id' => Media::factory()->create()->id]);

        Materiel::factory()->create(['designation' => 'HP EliteBook 840', 'main_media_id' => Media::factory()->create()->id]);
        Materiel::factory()->create(['designation' => 'Sans Photo']);

        foreach (['/espace-client/connexion', '/espace-client/inscription', '/choisir-mon-ordinateur'] as $page) {
            $this->get($page)->assertOk()->assertSee('auth-showcase-row', false)->assertSee('KalanNet')->assertSee('HP EliteBook 840')
                ->assertDontSee('Projet Secret')->assertDontSee('Sans Photo');
        }
    }
}
