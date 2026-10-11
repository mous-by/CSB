<?php

namespace Tests\Feature\Contenus;

use App\Models\ActivityLog;
use App\Models\Prestation;
use App\Models\Temoignage;
use App\Models\User;
use Database\Seeders\ContenuInitialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Services et témoignages (cahier §4) : gérés par le gestionnaire de contenu, seuls les éléments publiés sont visibles.
 */
class PrestationTemoignageTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_the_gestionnaire_creates_a_service_with_a_unique_readable_address(): void
    {
        $this->actingAs(User::factory()->gestionnaire()->create());

        $this->get('/admin/prestations')->assertOk()->assertSee('SERVICES DE CSB');
        $this->post('/admin/prestations', ['title' => 'Réparation d\'imprimantes', 'icon' => 'bx-printer', 'summary' => 'Dépannage rapide.', 'sort_order' => 1, 'is_published' => '1'])
            ->assertRedirect('/admin/prestations');
        $this->post('/admin/prestations', ['title' => 'Réparation d\'imprimantes', 'icon' => 'bx-printer', 'summary' => 'Doublon.', 'sort_order' => 2])
            ->assertRedirect('/admin/prestations');

        $this->assertSame(['reparation-dimprimantes', 'reparation-dimprimantes-2'], Prestation::orderBy('id')->pluck('slug')->all());
        $this->assertFalse(Prestation::orderByDesc('id')->first()->is_published, 'Case décochée = masqué.');
        $this->assertTrue(ActivityLog::where('action', 'prestations.created')->exists());

        $this->post('/admin/prestations', ['title' => '', 'icon' => 'bx-inconnue', 'summary' => '', 'sort_order' => -1])
            ->assertSessionHasErrors(['title', 'icon', 'summary', 'sort_order']);
    }

    public function test_deletion_is_an_exception_for_the_gestionnaire(): void
    {
        $prestation = Prestation::factory()->create();

        $this->actingAs(User::factory()->gestionnaire()->create())->delete("/admin/prestations/{$prestation->id}")->assertForbidden();
        $this->actingAs(User::factory()->administrateur()->create())->delete("/admin/prestations/{$prestation->id}")->assertRedirect('/admin/prestations');
        $this->assertSoftDeleted($prestation);
    }

    public function test_only_published_services_and_testimonials_reach_the_site(): void
    {
        Prestation::factory()->create(['title' => 'Service visible', 'summary' => 'Résumé visible']);
        Prestation::factory()->hidden()->create(['title' => 'Service caché']);
        Temoignage::factory()->create(['author_name' => 'Client Content', 'content' => 'Très bon travail.']);
        Temoignage::factory()->create(['author_name' => 'Client Discret', 'is_published' => false]);

        $this->get('/')->assertOk()->assertSee('Service visible')->assertDontSee('Service caché')
            ->assertSee('Client Content')->assertDontSee('Client Discret');
        $this->get('/services')->assertOk()->assertSee('Résumé visible')->assertDontSee('Service caché');
    }

    public function test_the_commercial_cannot_manage_content(): void
    {
        $this->actingAs(User::factory()->commercial()->create());
        $this->get('/admin/prestations')->assertForbidden();
        $this->get('/admin/temoignages')->assertForbidden();
        $this->post('/admin/temoignages', ['author_name' => 'X', 'content' => 'Y', 'sort_order' => 0])->assertForbidden();
    }

    public function test_a_new_testimonial_is_hidden_unless_explicitly_published(): void
    {
        $this->actingAs(User::factory()->gestionnaire()->create())
            ->post('/admin/temoignages', ['author_name' => 'Awa', 'content' => 'Merci CSB.', 'sort_order' => 0])
            ->assertRedirect('/admin/temoignages');

        $this->assertFalse(Temoignage::firstOrFail()->is_published);
    }

    public function test_the_initial_services_are_seeded_idempotently(): void
    {
        $this->seed(ContenuInitialSeeder::class);
        $this->seed(ContenuInitialSeeder::class);

        $this->assertSame(count(ContenuInitialSeeder::PRESTATIONS), Prestation::count());
        $this->get('/services')->assertSee('Réparation et maintenance')->assertSee('Applications web et mobiles');
    }
}
