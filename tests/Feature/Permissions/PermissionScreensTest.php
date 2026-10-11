<?php

namespace Tests\Feature\Permissions;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écrans de gestion des permissions, avec les deux voies (D23) : réservés au superadmin.
 */
class PermissionScreensTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->superadmin = $this->superadmin();
    }

    public function test_the_screens_are_forbidden_to_everyone_but_the_superadmin(): void
    {
        $admin = User::factory()->administrateur()->create();
        $chef = User::factory()->commercial()->create();
        $uris = ['/admin/permissions', '/admin/roles', '/admin/user-permissions'];

        foreach ($uris as $uri) {
            $this->get($uri)->assertRedirect('/admin/login');
        }
        foreach ($uris as $uri) {
            $this->actingAs($admin)->get($uri)->assertForbidden();
            $this->actingAs($chef)->get($uri)->assertForbidden();
            $this->actingAs($this->superadmin)->get($uri)->assertOk();
        }
    }

    public function test_writes_are_forbidden_to_the_admin_national_even_with_a_forged_request(): void
    {
        $admin = User::factory()->administrateur()->create();
        $commercial = User::factory()->commercial()->create();

        $this->actingAs($admin)->post('/admin/permissions', ['name' => 'etiquettes.print'])->assertForbidden();
        $this->actingAs($admin)->put("/admin/users/{$commercial->id}/permissions", ['permissions' => []])->assertForbidden();
        $this->actingAs($admin)->put('/admin/roles/'.Role::findByName('commercial', 'web')->id.'/permissions', ['permissions' => []])->assertForbidden();
        $this->assertFalse(Permission::where('name', 'etiquettes.print')->exists());
    }

    public function test_the_catalog_shows_both_sources_and_the_reserved_ones(): void
    {
        Permission::create(['name' => 'etiquettes.print', 'guard_name' => 'web', 'source' => 'custom']);

        $this->actingAs($this->superadmin)->get('/admin/permissions')
            ->assertOk()
            ->assertSee('users.view')
            ->assertSee('etiquettes.print')
            ->assertSee('Manifeste')
            ->assertSee('Interface')
            ->assertSee('Réservée');
    }

    public function test_the_search_returns_only_the_partial_for_ajax_requests(): void
    {
        $response = $this->actingAs($this->superadmin)->get('/admin/permissions?search=system', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $response->assertSee('system.view')->assertDontSee('users.view')->assertDontSee('RÉFÉRENTIEL DES PERMISSIONS');
    }

    public function test_the_superadmin_creates_and_deletes_a_custom_permission_from_the_interface(): void
    {
        $this->actingAs($this->superadmin)->post('/admin/permissions', ['name' => 'etiquettes.print'])->assertRedirect('/admin/permissions');

        $permission = Permission::where('name', 'etiquettes.print')->firstOrFail();
        $this->assertSame('custom', $permission->source);
        $this->assertSame(1, ActivityLog::where('action', 'permissions.created')->count());

        $this->delete("/admin/permissions/{$permission->id}")->assertRedirect('/admin/permissions');
        $this->assertFalse(Permission::where('name', 'etiquettes.print')->exists());
    }

    public function test_invalid_reserved_or_duplicate_names_are_refused_with_a_message(): void
    {
        $this->actingAs($this->superadmin);

        foreach (['sans point', 'Majuscule.view', 'system.reboot', 'users.view'] as $name) {
            $this->from('/admin/permissions')->post('/admin/permissions', ['name' => $name])->assertRedirect('/admin/permissions')->assertSessionHasErrors('name');
        }
        $this->assertSame(0, Permission::custom()->count());
    }

    public function test_a_manifest_permission_cannot_be_deleted_from_the_interface(): void
    {
        $permission = Permission::where('name', 'users.view')->firstOrFail();

        $this->actingAs($this->superadmin)->from('/admin/permissions')->delete("/admin/permissions/{$permission->id}")->assertSessionHasErrors('permission');
        $this->assertTrue(Permission::where('name', 'users.view')->exists());
    }

    public function test_the_roles_catalog_lists_the_roles_and_their_manifest_defaults(): void
    {
        $this->actingAs($this->superadmin)->get('/admin/roles')
            ->assertOk()
            ->assertSee('Administrateur')
            ->assertSee('Gestionnaire de contenu')
            ->assertSee('Commercial')
            ->assertSee('users.reset_password')
            ->assertSee('Gate::before');
    }

    public function test_custom_permissions_are_attached_to_a_role_from_the_roles_screen(): void
    {
        Permission::create(['name' => 'etiquettes.print', 'guard_name' => 'web', 'source' => 'custom']);
        $role = Role::findByName('administrateur', 'web');

        $this->actingAs($this->superadmin)->put("/admin/roles/{$role->id}/permissions", ['permissions' => ['etiquettes.print']])->assertRedirect('/admin/roles');
        $this->assertTrue($role->fresh()->hasPermissionTo('etiquettes.print'));
        $this->assertTrue($role->fresh()->hasPermissionTo('users.view'), 'Les défauts du manifeste restent.');

        $this->put("/admin/roles/{$role->id}/permissions", ['permissions' => ['users.delete']])->assertSessionHasErrors('permissions');
    }

    public function test_the_user_permissions_screen_offers_only_the_pool_of_the_targeted_role(): void
    {
        Permission::create(['name' => 'etiquettes.print', 'guard_name' => 'web', 'source' => 'custom']);
        $chef = User::factory()->administrateur()->create(['name' => 'Chef Pool']);

        $page = $this->actingAs($this->superadmin)->get("/admin/user-permissions?user={$chef->id}")->assertOk();

        $page->assertSee('users.delete')->assertSee('etiquettes.print')->assertSee('users.view'); // héritée
        $page->assertDontSee('value="system.view"', false)->assertDontSee('value="audit.view"', false);
        $page->assertDontSee('<option value="'.$this->superadmin->id.'"', false); // absent de la liste déroulante (son nom reste dans la barre du haut)
    }

    public function test_an_exception_is_granted_then_used_then_removed(): void
    {
        $chef = User::factory()->administrateur()->create();

        $this->actingAs($this->superadmin)->put("/admin/users/{$chef->id}/permissions", ['permissions' => ['users.delete']])
            ->assertRedirect("/admin/user-permissions?user={$chef->id}");
        $this->assertTrue($chef->fresh()->can('users.delete'));
        $this->assertSame(1, ActivityLog::where('action', 'permissions.user_synced')->count());

        $this->put("/admin/users/{$chef->id}/permissions", ['permissions' => []])->assertRedirect();
        $this->assertFalse($chef->fresh()->can('users.delete'));
    }

    public function test_a_reserved_or_out_of_pool_permission_cannot_be_forced_through(): void
    {
        $commercial = User::factory()->commercial()->create();

        $this->actingAs($this->superadmin)->put("/admin/users/{$commercial->id}/permissions", ['permissions' => ['system.maintain']])->assertSessionHasErrors('permissions');
        $this->put("/admin/users/{$commercial->id}/permissions", ['permissions' => ['users.delete']])->assertSessionHasErrors('permissions');
        $this->assertCount(0, $commercial->fresh()->permissions);
    }

    public function test_the_legacy_show_route_redirects_to_the_screen(): void
    {
        $chef = User::factory()->commercial()->create();

        $this->actingAs($this->superadmin)->get("/admin/users/{$chef->id}/permissions")->assertRedirect("/admin/user-permissions?user={$chef->id}");
    }
}
