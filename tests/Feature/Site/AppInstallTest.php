<?php

namespace Tests\Feature\Site;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Applications installables (PWA) avec l'icône de CSB : le site public et l'administration ; la fenêtre
 * « Installer l'application » s'ouvre à la page qui suit la connexion.
 */
class AppInstallTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_both_manifests_are_public_and_point_to_the_csb_icons(): void
    {
        $site = $this->get('/manifest.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json');
        $site->assertJson(['id' => '/', 'start_url' => '/', 'scope' => '/', 'display' => 'standalone', 'short_name' => 'CSB']);
        $this->assertCount(3, $site->json('icons'));
        foreach ($site->json('icons') as $icon) {
            $this->assertFileExists(public_path(parse_url($icon['src'], PHP_URL_PATH)));
        }

        // Icône propre à l'administration (fond bleu, bandeau « ADMIN ») : les deux applications ne se confondent pas.
        $team = $this->get('/manifest-equipe.webmanifest')->assertOk()
            ->assertJson(['id' => '/admin', 'start_url' => '/admin', 'scope' => '/admin', 'display' => 'standalone']);
        $this->assertCount(3, $team->json('icons'));
        foreach ($team->json('icons') as $icon) {
            $this->assertStringContainsString('/assets/pwa/admin-icon-', $icon['src']);
            $this->assertFileExists(public_path(parse_url($icon['src'], PHP_URL_PATH)));
        }
        $this->assertFileExists(public_path('assets/pwa/apple-touch-icon.png'));
        $this->assertFileExists(public_path('assets/pwa/admin-apple-touch-icon.png'));
    }

    public function test_each_layout_links_its_own_manifest(): void
    {
        $this->get('/')->assertOk()->assertSee('/manifest.webmanifest', false)->assertDontSee('manifest-equipe', false)->assertSee('data-prompt="0"', false);
        $this->get('/admin/login')->assertOk()->assertSee('/manifest-equipe.webmanifest', false);
        $this->actingAs(User::factory()->administrateur()->create())->get('/admin')->assertOk()
            ->assertSee('/manifest-equipe.webmanifest', false)->assertSee('admin-apple-touch-icon.png', false)->assertSee('data-pwa-modal', false)->assertSee('Installer l&#039;administration CSB', false);
    }

    public function test_the_install_window_opens_once_after_a_client_logs_in(): void
    {
        $client = Client::factory()->create(['email' => 'awa@exemple.ml']);

        $this->post('/espace-client/connexion', ['login' => 'awa@exemple.ml', 'password' => 'Client-Pass-1'])->assertRedirect();
        $this->assertSame('site', session('pwa_prompt'));

        $this->get('/espace-client')->assertOk()->assertSee('data-prompt="1"', false)->assertSee('Installer l&#039;application CSB', false);
        $this->get('/espace-client')->assertOk()->assertSee('data-prompt="0"', false);
        $this->assertTrue(auth('client')->user()->is($client));
    }

    public function test_the_admin_install_window_opens_after_a_team_member_logs_in(): void
    {
        User::factory()->administrateur()->create(['phone' => '+22370000055', 'password' => Hash::make('Secret-Pass-1')]);

        $this->post('/admin/login', ['phone' => '70 00 00 55', 'password' => 'Secret-Pass-1'])->assertRedirect('/admin');
        $this->assertSame('team', session('pwa_prompt'));

        // Le site public ne consomme pas la fenêtre de l'administration.
        $this->get('/')->assertSee('data-prompt="0"', false);
        $this->get('/admin')->assertOk()->assertSee('data-prompt="1"', false);
        $this->get('/admin')->assertOk()->assertSee('data-prompt="0"', false);
    }
}
