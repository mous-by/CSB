<?php

namespace Tests\Feature\Modules;

use App\Models\Client;
use App\Models\User;
use App\Services\Statistiques\SiteStats;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Statistiques du site : une page vue par visiteur humain du site public (ni robot, ni aperçu de lien, ni AJAX, ni
 * équipe connectée, ni erreur), visites par jour, provenance, appareil, écran et tableau de bord, purge à 13 mois.
 */
class StatistiquesTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private const MOBILE = 'Mozilla/5.0 (Linux; Android 14; SM-A146B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    public function test_only_human_page_views_of_the_public_site_are_counted(): void
    {
        $this->flushHeaders()->withHeaders(['User-Agent' => self::MOBILE, 'Referer' => 'https://www.google.com/'])->get('/')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::MOBILE, 'Referer' => 'http://localhost/'])->get('/services')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::DESKTOP])->get('/materiel')->assertOk();

        // Non comptés : robots, aperçu WhatsApp, AJAX, préchargement, fichiers techniques, erreurs, administration.
        $this->flushHeaders()->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => 'WhatsApp/2.24.1 A'])->get('/')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::DESKTOP, 'X-Requested-With' => 'XMLHttpRequest'])->get('/materiel');
        $this->flushHeaders()->withHeaders(['User-Agent' => self::DESKTOP, 'Sec-Purpose' => 'prefetch'])->get('/');
        $this->flushHeaders()->withHeaders(['User-Agent' => self::DESKTOP])->get('/sitemap.xml')->assertOk();
        $this->flushHeaders()->withHeaders(['User-Agent' => self::DESKTOP])->get('/page-qui-n-existe-pas')->assertNotFound();
        $this->syncPermissions();
        $this->flushHeaders()->actingAs(User::factory()->administrateur()->create())->withHeaders(['User-Agent' => self::DESKTOP])->get('/')->assertOk();

        $rows = DB::table('page_vues')->orderBy('id')->get();
        $this->assertCount(3, $rows);
        $this->assertSame(['google', 'interne', 'direct'], $rows->pluck('source')->all());
        $this->assertSame(['telephone', 'telephone', 'ordinateur'], $rows->pluck('device')->all());
        $this->assertSame($rows[0]->visitor, $rows[1]->visitor, 'même visiteur le même jour');
        $this->assertNotSame($rows[0]->visitor, $rows[2]->visitor);
        $this->assertSame(32, strlen($rows[0]->visitor));
        $this->assertSame('site.home', $rows[0]->route);
    }

    public function test_the_administrator_reads_the_statistics_and_the_dashboard(): void
    {
        $this->syncPermissions();
        $insert = fn (string $day, string $visitor, string $path, string $source, string $device) => DB::table('page_vues')->insert([
            'day' => $day, 'visitor' => str_pad($visitor, 32, '0'), 'path' => $path, 'route' => $path === '/' ? 'site.home' : null, 'source' => $source, 'device' => $device, 'created_at' => now(),
        ]);
        $insert(today()->toDateString(), 'a', '/', 'google', 'telephone');
        $insert(today()->toDateString(), 'a', '/materiel/hp-840', 'interne', 'telephone');
        $insert(today()->toDateString(), 'b', '/', 'facebook', 'ordinateur');
        $insert(today()->subDays(3)->toDateString(), 'a', '/', 'direct', 'telephone');
        $insert(today()->subDays(400)->toDateString(), 'z', '/', 'direct', 'telephone');
        Client::factory()->create(['name' => 'Fatoumata Coulibaly']);

        $admin = User::factory()->administrateur()->create();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Fréquentation du site')->assertSee('Visites aujourd&#039;hui', false);

        $page = $this->get('/admin/statistiques?periode=7')->assertOk()
            ->assertSee('PAGES LES PLUS VUES')->assertSee('Accueil')->assertSee('/materiel/hp-840')
            ->assertSee('Google')->assertSee('Facebook / Messenger')->assertSee('Téléphone')->assertSee('Fatoumata Coulibaly');
        $this->assertSame(3, $page->viewData('report')['visits'], '3 visites : a et b aujourd\'hui, a il y a 3 jours');
        $this->assertSame(4, $page->viewData('report')['views']);
        $this->assertSame(1, $page->viewData('report')['clients']);

        foreach ([30, 90, 365] as $days) {
            $this->get("/admin/statistiques?periode={$days}")->assertOk();
        }

        $this->actingAs(User::factory()->commercial()->create())->get('/admin/statistiques')->assertForbidden();

        $this->artisan('stats:purge')->assertSuccessful();
        $this->assertSame(4, DB::table('page_vues')->count(), 'la page vue de plus de 13 mois est supprimée');
        $this->assertSame('1', DB::table('app_settings')->where('key', SiteStats::ARCHIVED_VISITS_KEY)->value('value'), 'sa visite reste dans le total');
    }

    public function test_the_home_page_shows_the_live_number_of_visits(): void
    {
        foreach (['a', 'b'] as $visitor) {
            DB::table('page_vues')->insert(['day' => today()->subDay()->toDateString(), 'visitor' => str_pad($visitor, 32, '0'), 'path' => '/', 'route' => 'site.home', 'source' => 'direct', 'device' => 'ordinateur', 'created_at' => now()]);
        }
        DB::table('app_settings')->insert(['key' => SiteStats::ARCHIVED_VISITS_KEY, 'value' => '40', 'created_at' => now(), 'updated_at' => now()]);
        Client::factory()->count(3)->create();
        app(SiteSettings::class)->save(['home.stats' => "+50|Projets réalisés|bx-cog\n{visiteurs}|Visites du site|bx-show\n{clients}|Comptes clients|bx-user"]);

        $this->flushHeaders()->get('/')->assertOk()
            ->assertSee('data-count="42"', false)->assertSee('Visites du site')
            ->assertSee('data-count="3"', false)->assertSee('Comptes clients')
            ->assertDontSee('{visiteurs}');
    }
}
