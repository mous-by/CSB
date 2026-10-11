<?php

namespace Tests\Feature\Users;

use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Utilisateurs : liste (User::visibleTo), création (mot de passe temporaire affiché une fois), modification,
 * activation, réinitialisation, révocation, suppression — plafond de rôle partout (CLAUDE.md §4.2).
 */
class UserScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->superadmin = $this->superadmin();
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/admin/users')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->commercial()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs($this->superadmin)->get('/admin/users')->assertOk()->assertSee('UTILISATEURS');
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs($this->superadmin)->get('/admin/users')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_an_administrateur_sees_everyone_but_superadmins(): void
    {
        User::factory()->administrateur()->create(['name' => 'Pair Admin']);
        User::factory()->commercial()->create(['name' => 'Un Commercial']);

        $page = $this->actingAs(User::factory()->administrateur()->create())->get('/admin/users')->assertOk();
        $page->assertSee('Pair Admin')->assertSee('Un Commercial')->assertDontSee($this->superadmin->name);
    }

    public function test_an_administrateur_creates_a_commercial_with_a_temporary_password(): void
    {
        $admin = User::factory()->administrateur()->create();

        $this->actingAs($admin)
            ->post('/admin/users', ['name' => 'Nouveau Commercial', 'phone' => '70 00 00 32', 'role' => 'commercial'])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('temporary_password')
            ->assertSessionHas('temporary_password_for', 'Nouveau Commercial');

        $user = User::where('phone', '+22370000032')->firstOrFail();
        $this->assertSame(RoleName::Commercial, $user->roleName());
        $this->assertTrue($user->must_change_password);
        $this->assertSame(1, ActivityLog::where('action', 'users.created')->where('subject_id', $user->id)->count());
    }

    public function test_an_administrateur_cannot_create_an_administrateur_or_a_superadmin(): void
    {
        $admin = User::factory()->administrateur()->create();

        foreach (['administrateur', 'superadmin'] as $i => $role) {
            $this->actingAs($admin)->post('/admin/users', ['name' => 'Z', 'phone' => '70 00 00 3'.$i, 'role' => $role])
                ->assertSessionHasErrors('role');
        }

        $this->assertFalse(User::whereIn('phone', ['+22370000030', '+22370000031'])->exists());
    }

    public function test_updating_name_and_phone(): void
    {
        $admin = User::factory()->administrateur()->create();
        $commercial = User::factory()->commercial()->create(['name' => 'Ancien Nom']);

        $this->actingAs($admin)
            ->put("/admin/users/{$commercial->id}", ['name' => 'Nouveau Nom', 'phone' => $commercial->phone])
            ->assertRedirect('/admin/users');

        $this->assertSame('Nouveau Nom', $commercial->fresh()->name);
    }

    public function test_an_administrateur_cannot_manage_a_peer_administrateur(): void
    {
        $admin = User::factory()->administrateur()->create();
        $peer = User::factory()->administrateur()->create(['name' => 'Pair']);

        $this->actingAs($admin)->put("/admin/users/{$peer->id}", ['name' => 'Changé', 'phone' => $peer->phone])->assertForbidden();
        $this->actingAs($admin)->delete("/admin/users/{$peer->id}")->assertForbidden();
        $this->assertSame('Pair', $peer->fresh()->name);
    }

    public function test_deactivating_revokes_sessions(): void
    {
        $admin = User::factory()->administrateur()->create();
        $commercial = User::factory()->commercial()->create();
        DB::table('sessions')->insert(['id' => 'sess-u1', 'user_id' => $commercial->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);

        $this->actingAs($admin)->put("/admin/users/{$commercial->id}", [
            'name' => $commercial->name, 'phone' => $commercial->phone, 'is_active' => '0',
        ])->assertRedirect('/admin/users');

        $this->assertFalse($commercial->fresh()->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $commercial->id)->count());
    }

    public function test_resetting_the_password_shows_it_once(): void
    {
        $admin = User::factory()->administrateur()->create();
        $commercial = User::factory()->commercial()->create();

        $this->actingAs($admin)->post("/admin/users/{$commercial->id}/reset-password")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('temporary_password');

        $this->assertTrue($commercial->fresh()->must_change_password);
        $this->assertSame(1, ActivityLog::where('action', 'auth.password_reset')->count());
    }

    public function test_revoking_access_clears_sessions_without_deactivating(): void
    {
        $admin = User::factory()->administrateur()->create();
        $commercial = User::factory()->commercial()->create();
        DB::table('sessions')->insert(['id' => 'sess-u2', 'user_id' => $commercial->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);

        $this->actingAs($admin)->post("/admin/users/{$commercial->id}/revoke-access")->assertRedirect('/admin/users');

        $this->assertTrue($commercial->fresh()->is_active, 'Révoquer ne désactive pas le compte.');
        $this->assertSame(0, DB::table('sessions')->where('user_id', $commercial->id)->count());
    }

    public function test_deleting_a_user_is_a_soft_delete_and_requires_its_permission(): void
    {
        $admin = User::factory()->administrateur()->create();
        $commercial = User::factory()->commercial()->create();

        // users.delete n'est pas un défaut de l'administrateur : elle s'attribue en exception.
        $this->actingAs($admin)->delete("/admin/users/{$commercial->id}")->assertForbidden();

        $admin->givePermissionTo('users.delete');
        $this->actingAs($admin->fresh())->delete("/admin/users/{$commercial->id}")->assertRedirect('/admin/users');

        $this->assertSoftDeleted($commercial);
    }

    public function test_write_actions_are_forbidden_without_their_specific_permission(): void
    {
        // Le commercial n'a aucun défaut sur le module users (config/modules/users.php).
        $commercial = User::factory()->commercial()->create();
        $other = User::factory()->commercial()->create();

        $this->actingAs($commercial)->post("/admin/users/{$other->id}/reset-password")->assertForbidden();
        $this->actingAs($commercial)->post('/admin/users', ['name' => 'X', 'phone' => '70 00 00 50', 'role' => 'commercial'])->assertForbidden();
    }

    public function test_the_last_active_superadmin_cannot_be_deactivated_or_deleted_from_the_screen(): void
    {
        $this->actingAs($this->superadmin)
            ->put("/admin/users/{$this->superadmin->id}", ['name' => $this->superadmin->name, 'phone' => $this->superadmin->phone, 'is_active' => '0'])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');

        $this->assertTrue($this->superadmin->fresh()->is_active);

        $this->actingAs($this->superadmin)->delete("/admin/users/{$this->superadmin->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($this->superadmin);
    }

    public function test_the_list_shows_role_chips_and_a_sheet_per_user_without_secrets(): void
    {
        $admin = User::factory()->administrateur()->create();
        $commercial = User::factory()->commercial()->create(['name' => 'Agent Fiche']);

        $this->actingAs($admin)->get('/admin/users')->assertOk()
            ->assertSee('users-filter', false)
            ->assertSee('data-token="role-commercial"', false)
            ->assertSee('ficheUser-'.$commercial->id, false)
            ->assertSee('Agent Fiche')
            ->assertDontSee($commercial->password);
    }

    public function test_superadmin_accounts_are_invisible_to_other_roles(): void
    {
        $admin = User::factory()->administrateur()->create();
        $id = $this->superadmin->id;

        // Ni dans la liste, ni par son adresse : « introuvable », comme un compte qui n'existe pas.
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertDontSee($this->superadmin->name)->assertDontSee('Superadmin');
        $this->put("/admin/users/{$id}", ['name' => 'X', 'phone' => $this->superadmin->phone])->assertNotFound();
        $this->delete("/admin/users/{$id}")->assertNotFound();
        $this->post("/admin/users/{$id}/reset-password")->assertNotFound();
        $this->post("/admin/users/{$id}/revoke-access")->assertNotFound();
        $this->put('/admin/users/999999', ['name' => 'X'])->assertNotFound();

        // Son nom n'apparaît pas non plus comme auteur d'une opération.
        $this->assertSame('Administration', $this->superadmin->fresh()->displayName($admin));
        $this->assertSame($this->superadmin->name, $this->superadmin->fresh()->displayName($this->superadmin));
    }
}
