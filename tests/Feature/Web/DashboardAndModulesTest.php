<?php

namespace Tests\Feature\Web;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

class DashboardAndModulesTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();

        // Liste « à venir » de test : la vraie se vide à mesure que les modules sont livrés.
        config(['planned_modules' => [
            'inventaires' => ['label' => 'Inventaires de test', 'icon' => 'bx bx-clipboard', 'order' => 300, 'roles' => ['commercial'], 'summary' => 'Module prévu.', 'cahier' => '§15.3', 'open' => ['Point ouvert de test.']],
            'fidelite' => ['label' => 'Fidélité de test', 'icon' => 'bx bx-group', 'order' => 310, 'roles' => [], 'summary' => 'Autre module prévu.', 'cahier' => '§15.3', 'open' => []],
        ]]);
    }

    public function test_the_superadmin_supervision_shows_the_single_superadmin_alert(): void
    {
        $this->actingAs($this->superadmin())->get('/admin')
            ->assertOk()
            ->assertSee('Supervision générale')
            ->assertSee('Un seul superadmin actif');
    }

    public function test_no_alert_when_two_superadmins_are_active(): void
    {
        $this->superadmin();
        $second = $this->superadmin();

        $this->actingAs($second)->get('/admin')->assertOk()->assertDontSee('Un seul superadmin actif');
    }

    public function test_the_sidebar_lists_the_planned_evolutions_by_role(): void
    {
        // La liste « à venir » ne contient que les évolutions du cahier §15.3, hors périmètre V1.
        $expected = collect(config('planned_modules'))->pluck('label');

        foreach ([$this->superadmin(), User::factory()->administrateur()->create()] as $user) {
            $page = $this->actingAs($user)->get('/admin')->assertOk()->assertSee('Modules à venir');
            foreach ($expected as $label) {
                $page->assertSee($label);
            }
        }

        // Le commercial ne voit que les évolutions qui le concernent.
        $this->actingAs(User::factory()->commercial()->create())->get('/admin')->assertSee('Inventaires de test')->assertDontSee('Fidélité de test');
    }

    public function test_a_planned_module_page_is_a_sheet_with_open_points_and_no_data(): void
    {
        $this->actingAs($this->superadmin())->get('/admin/modules/inventaires')
            ->assertOk()
            ->assertSee('À venir')
            ->assertSee('Point ouvert de test.')
            ->assertSee('config/modules/inventaires.php');
    }

    public function test_an_unknown_planned_module_is_a_404(): void
    {
        $this->get('/admin/modules/inventaires')->assertRedirect('/admin/login'); // invité, avant toute authentification
        $this->actingAs(User::factory()->commercial()->create())->get('/admin/modules/n-existe-pas')->assertNotFound();
    }

    public function test_a_module_leaves_the_planned_list_when_its_manifest_exists(): void
    {
        $superadmin = $this->superadmin();
        config(['modules.partenaires' => ['label' => 'Partenaires', 'permissions' => ['view' => 'Voir'], 'roles' => []]]);

        $this->actingAs($superadmin)->get('/admin/modules/partenaires')->assertNotFound();
        $this->actingAs($superadmin)->get('/admin/modules/inventaires')->assertOk();
    }

    /** Le HTML de la sidebar seule (le corps de la page peut contenir les mêmes mots). */
    private function sidebar(string $html): string
    {
        preg_match('#<div class="sidebar-wrapper".*?</ul>\s*</div>#s', $html, $match);

        return $match[0] ?? '';
    }

    public function test_the_settings_entry_replaces_roles_and_sits_below_every_other_menu(): void
    {
        $sidebar = $this->sidebar($this->actingAs($this->superadmin())->get('/admin')->getContent());

        $this->assertStringContainsString('Paramètres', $sidebar);
        $this->assertStringNotContainsString('>Rôles<', $sidebar, 'L\'entrée « Rôles » est devenue « Paramètres ».');

        // Sous « Tableau de bord », sous tous les modules à venir, en dernier.
        $lastPlanned = collect(config('planned_modules'))->pluck('label')->map(fn ($label) => strrpos($sidebar, $label))->max();
        $this->assertGreaterThan($lastPlanned, strrpos($sidebar, 'Paramètres'));
        $this->assertGreaterThan(strpos($sidebar, 'Modules à venir'), strpos($sidebar, 'Paramètres'));
        $this->assertNotSame('', $sidebar);
    }

    public function test_permissions_and_assignment_are_not_in_the_sidebar_but_in_the_settings_menu(): void
    {
        $superadmin = $this->superadmin();

        $sidebar = $this->sidebar($this->actingAs($superadmin)->get('/admin')->getContent());
        $this->assertStringNotContainsString('Attribution', $sidebar);
        $this->assertStringNotContainsString('bx-shield-alt-2', $sidebar);
        $this->assertStringNotContainsString('bx-user-check', $sidebar);
        // Audit et Système non plus : ils s'ouvrent depuis Paramètres.
        $this->assertStringNotContainsString('bx-list-check', $sidebar);
        $this->assertStringNotContainsString('bx-server', $sidebar);

        // Ils restent accessibles depuis la zone Paramètres.
        $page = $this->get('/admin/roles')->assertOk()->assertSee('PARAMÈTRES');
        $page->assertSee('Permissions')->assertSee('Attribution des permissions')->assertSee('Rôles')
            ->assertSee(route('audit.index'))->assertSee(route('system.index'));
    }

    public function test_audit_and_system_are_reached_from_the_settings_menu_and_show_it(): void
    {
        $superadmin = $this->superadmin();

        foreach (['/admin/audit' => 'audit.index', '/admin/system' => 'system.index'] as $uri => $route) {
            $this->actingAs($superadmin)->get($uri)->assertOk()
                ->assertSee('PARAMÈTRES')
                ->assertSee('list-group-item list-group-item-action active', false)
                ->assertSee(route($route));
        }
    }

    public function test_settings_stays_highlighted_on_every_settings_page(): void
    {
        $superadmin = $this->superadmin();

        foreach (['/admin/roles', '/admin/permissions', '/admin/user-permissions', '/admin/audit', '/admin/system'] as $uri) {
            $sidebar = $this->sidebar($this->actingAs($superadmin)->get($uri)->getContent());
            $this->assertMatchesRegularExpression('#mm-active[^>]*>\s*<div class="parent-icon"><i class=\'bx bx-cog\'#', $sidebar, "Paramètres doit être actif sur $uri");
        }
    }

    public function test_the_settings_entry_is_hidden_from_roles_without_access(): void
    {
        // L'administrateur voit Paramètres (Utilisateurs) ; le commercial n'a par défaut aucun de ces écrans.
        $sidebar = $this->sidebar($this->actingAs(User::factory()->commercial()->create())->get('/admin')->getContent());

        $this->assertStringNotContainsString('Paramètres', $sidebar);
    }

    public function test_the_settings_entry_target_is_not_hardcoded_to_roles(): void
    {
        // ModuleRegistry::firstAccessibleSettingsItem() : fail-closed sans aucun accès, et le premier écran
        // réellement accessible sinon — jamais figé sur /roles (réservée au superadmin).
        $registry = app(ModuleRegistry::class);

        $this->assertNull($registry->firstAccessibleSettingsItem(User::factory()->commercial()->create()));

        $item = $registry->firstAccessibleSettingsItem($this->superadmin());
        $this->assertNotNull($item);
        $this->assertContains($item['route'], collect($registry->settingsItems())->pluck('route')->all());
    }

    public function test_the_profile_shows_the_role(): void
    {
        $commercial = User::factory()->commercial()->create(['name' => 'Commercial Test']);

        $this->actingAs($commercial)->get('/admin/profile')->assertOk()->assertSee('Commercial Test')->assertSee('Commercial');
    }

    public function test_pages_never_reference_the_internet(): void
    {
        $login = $this->get('/admin/login')->getContent();
        $pages = $login.$this->actingAs($this->superadmin())->get('/admin')->getContent().$this->get('/admin/profile')->getContent().$this->get('/admin/roles')->getContent().$this->get('/admin/permissions')->getContent();

        preg_match_all('#(?:src|href|action)=["\'](https?://[^"\']+)#', $pages, $matches);
        // asset() et route() produisent des URL absolues sur le serveur lui-même : seules les URL vers un AUTRE hôte sont interdites.
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $external = array_values(array_unique(array_filter($matches[1], fn ($url) => parse_url($url, PHP_URL_HOST) !== $appHost)));
        $this->assertSame([], $external, 'Ressource externe dans une page (D15).');
        foreach (['fonts.googleapis', 'unpkg.com', 'jsdelivr', 'cdnjs', 'code.jquery.com'] as $cdn) {
            $this->assertStringNotContainsString($cdn, $pages);
        }
    }

    public function test_the_audit_table_of_the_supervision_lists_real_entries(): void
    {
        $superadmin = $this->superadmin(['name' => 'Moustapha Test']);
        $this->actingAs($superadmin);
        User::factory()->administrateur()->create(['name' => 'Cible Test']);

        $this->get('/admin')->assertOk()->assertSee('DERNIÈRES ACTIONS')->assertSee('Cible Test');
        $this->assertGreaterThan(0, ActivityLog::count());
    }
}
