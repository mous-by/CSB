<?php

namespace Tests\Feature\Site;

use App\Models\Logiciel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Site public (cahier §4, §8, §11) : pages accessibles sans compte, coordonnées et appels à l'action, métadonnées,
 * page 404 utile.
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_institutional_pages_are_public_and_have_unique_titles(): void
    {
        $titles = [];
        foreach (['/', '/a-propos', '/services', '/mentions-legales', '/confidentialite'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();
            preg_match('#<title>(.*?)</title>#', $html, $title);
            $titles[] = $title[1];
            $this->assertSame(1, substr_count($html, '<h1'), "$uri : une seule balise h1.");
            $this->assertStringContainsString('<link rel="canonical"', $html);
            $this->assertStringContainsString('<meta name="description"', $html);
            $this->assertStringContainsString('og:title', $html);
        }

        $this->assertSame($titles, array_unique($titles), 'Chaque page a son propre titre.');
    }

    public function test_the_home_page_shows_the_company_contacts_and_whatsapp(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Qualité • Fiabilité • Performance')
            ->assertSee('74 74 56 69')->assertSee('67 20 57 36')->assertSee('barrymoustapha908@gmail.com')
            ->assertSee('tel:+22374745669', false)
            ->assertSee('https://wa.me/22374745669?text=', false)
            ->assertSee('"@type":"Organization"', false);
    }

    public function test_the_404_page_leads_back_to_the_site(): void
    {
        $this->get('/page-inexistante')->assertNotFound()
            ->assertSee('Page introuvable')->assertSee('Retour à l\'accueil', false)->assertSee('noindex', false);
    }

    public function test_an_admin_404_keeps_the_admin_layout(): void
    {
        $this->get('/admin/n-existe-pas')->assertNotFound()->assertSee('Retour à l\'administration', false)->assertDontSee('whatsapp-float');
    }

    public function test_the_site_loads_no_external_resource(): void
    {
        $html = $this->get('/')->getContent();
        preg_match_all('#<(?:link|script|img)[^>]+(?:src|href)=["\'](https?://[^"\']+)#', $html, $matches);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $external = array_filter($matches[1], fn ($url) => parse_url($url, PHP_URL_HOST) !== $appHost);

        $this->assertSame([], array_values($external), 'Aucune ressource externe (D15).');
    }

    /**
     * Régression du 2026-10-11 : avec un vrai cache (fichier, base), la deuxième page affichée échouait (500) parce que
     * le menu « Nos solutions » mettait des modèles en cache, que Laravel 13 refuse de désérialiser.
     */
    public function test_pages_still_work_once_the_menu_is_cached_by_a_real_store(): void
    {
        config(['cache.default' => 'file', 'cache.stores.file.path' => storage_path('framework/cache/test-'.uniqid())]);
        Logiciel::factory()->create(['is_published' => true]);

        $this->get('/')->assertOk();
        $this->get('/services')->assertOk();
        $this->get('/logiciels')->assertOk();

        Cache::flush();
    }
}
