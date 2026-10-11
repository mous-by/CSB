<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Tableau de bord par rôle (cahier §7) : chaque rôle ne voit que ce qui le concerne — administrateur : comptes et
 * exploitation ; superadmin : tout, plus la supervision. Même règle pour la cloche de notifications.
 */
class RoleDashboardTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_the_commercial_sees_nothing_of_the_administration(): void
    {
        $this->actingAs(User::factory()->commercial()->create())->get('/admin')->assertOk()
            ->assertSee('Demandes commerciales')
            ->assertDontSee('Comptes et accès')->assertDontSee('Dernière sauvegarde')->assertDontSee('ACTIVITÉ PAR AGENT')
            ->assertDontSee('DERNIÈRES ACTIONS');
    }

    public function test_the_administrateur_sees_accounts_and_operations(): void
    {
        $this->actingAs(User::factory()->administrateur()->create())->get('/admin')->assertOk()
            ->assertSee('Administration du site')
            ->assertSee('Comptes et accès')->assertSee('DERNIÈRES CONNEXIONS')
            ->assertSee('Exploitation')->assertSee('Dernière sauvegarde')->assertSee('Notifications e-mail')
            ->assertDontSee('ACTIVITÉ PAR AGENT');
    }

    public function test_the_superadmin_has_the_widest_view(): void
    {
        $this->actingAs($this->superadmin())->get('/admin')->assertOk()
            ->assertSee('Supervision générale')
            ->assertSee('Comptes et accès')->assertSee('Exploitation')
            ->assertSee('Supervision')->assertSee('ACTIVITÉ PAR AGENT (30 JOURS)')->assertSee('DERNIÈRES ACTIONS');
    }

    public function test_notifications_follow_the_same_rule(): void
    {
        config(['backup.path' => sys_get_temp_dir().'/csb-no-backup-'.uniqid()]);

        $this->actingAs(User::factory()->administrateur()->create())->get('/admin')->assertSee('Aucune sauvegarde de la base n');
        $this->actingAs(User::factory()->commercial()->create())->get('/admin')->assertDontSee('Aucune sauvegarde de la base n');
    }

    public function test_a_user_without_any_module_gets_a_clear_message(): void
    {
        $bare = User::factory()->commercial()->create();
        $bare->roles->first()->syncPermissions([]);

        $this->actingAs($bare->fresh())->get('/admin')->assertOk()->assertSee('Aucun module ne vous est encore attribué');
    }

    public function test_the_dashboard_links_to_the_public_site(): void
    {
        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin')->assertOk()
            ->assertSee('Voir le site')->assertSee(route('site.home'), false);
    }
}
