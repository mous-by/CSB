<?php

namespace Tests\Feature\Site;

use App\Models\Article;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Redirection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Référencement (cahier §11.1, REC-10) et recherche : sitemap, robots.txt, redirections des adresses modifiées,
 * recherche et suggestions sur les seuls contenus publiés.
 */
class SeoAndSearchTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    public function test_the_sitemap_lists_public_pages_and_published_contents_only(): void
    {
        Logiciel::factory()->create(['slug' => 'kalannet']);
        Logiciel::factory()->draft()->create(['slug' => 'secret']);
        Materiel::factory()->create(['slug' => 'hp-probook']);
        Article::factory()->create(['slug' => 'conseil-du-jour']);
        Article::factory()->create(['slug' => 'futur', 'published_at' => now()->addWeek()]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertStringContainsString('/logiciels/kalannet</loc>', $xml);
        $this->assertStringContainsString('/materiel/hp-probook</loc>', $xml);
        $this->assertStringContainsString('/actualites/conseil-du-jour</loc>', $xml);
        $this->assertStringContainsString('/contact</loc>', $xml);
        $this->assertStringNotContainsString('secret', $xml);
        $this->assertStringNotContainsString('futur', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_robots_txt_hides_the_administration_and_any_non_production_site(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

        $this->app['env'] = 'production';
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin/')->assertSee('Sitemap: ');
    }

    public function test_a_changed_address_redirects_permanently_and_chains_are_shortened(): void
    {
        $this->syncPermissions();
        $logiciel = Logiciel::factory()->create(['name' => 'KalanNet', 'slug' => 'kalan-net']);

        $logiciel->update(['slug' => 'kalannet']);
        $logiciel->update(['slug' => 'kalannet-ecoles']);

        $this->get('/logiciels/kalan-net')->assertRedirect('/logiciels/kalannet-ecoles')->assertStatus(301);
        $this->get('/logiciels/kalannet')->assertRedirect('/logiciels/kalannet-ecoles');
        $this->get('/logiciels/kalannet-ecoles')->assertOk();
        $this->assertSame(1, Redirection::where('from_path', '/logiciels/kalan-net')->value('hits'));

        // Retour à une ancienne adresse : elle ne redirige plus.
        $logiciel->update(['slug' => 'kalannet']);
        $this->get('/logiciels/kalannet')->assertOk();
        $this->get('/logiciels/kalannet-ecoles')->assertRedirect('/logiciels/kalannet');
    }

    public function test_an_unknown_address_is_a_useful_404(): void
    {
        $this->get('/ancienne-page-inconnue')->assertNotFound()->assertSee('Page introuvable');
        $this->actingAs(User::factory()->create())->get('/admin/inconnu')->assertNotFound()->assertSee('Retour à l\'administration', false);
    }

    public function test_search_and_live_suggestions_cover_published_contents(): void
    {
        Logiciel::factory()->create(['name' => 'JAGO DANAYA', 'summary' => 'Gestion de stock et de caisse.']);
        Logiciel::factory()->draft()->create(['name' => 'Stock Secret', 'summary' => 'Brouillon de gestion de stock.']);
        Materiel::factory()->create(['designation' => 'Scanner de codes-barres', 'specs' => [['Usage', 'Gestion de stock']]]);

        $this->get('/recherche?q=stock')->assertOk()->assertSee('JAGO DANAYA')->assertSee('Scanner de codes-barres')->assertDontSee('Stock Secret')->assertSee('noindex', false);
        $this->get('/recherche?q=introuvable')->assertOk()->assertSee('Aucun résultat');

        $this->getJson('/recherche/suggestions?q=st')->assertOk()->assertJsonPath('groups.0.label', 'Logiciels')->assertJsonPath('groups.0.items.0.title', 'JAGO DANAYA');
        $this->getJson('/recherche/suggestions?q=s')->assertOk()->assertJsonPath('groups', []);
        $this->getJson('/recherche/suggestions?q=%25')->assertOk()->assertJsonPath('groups', []);
    }
}
