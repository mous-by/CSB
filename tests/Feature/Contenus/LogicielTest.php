<?php

namespace Tests\Feature\Contenus;

use App\Enums\CategoryType;
use App\Enums\LogicielStatus;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Logiciel;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Catalogue logiciel (cahier §5.1, §5.2, REC-03, REC-05) : créer, modifier, publier, archiver ; le site ne montre que
 * les fiches publiées, filtrables et recherchables.
 */
class LogicielTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $gestionnaire;

    private Category $education;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->syncPermissions();
        $this->gestionnaire = User::factory()->gestionnaire()->create();
        $this->education = Category::factory()->of(CategoryType::Logiciel)->create(['name' => 'Éducation et gestion scolaire']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'KalanNet', 'summary' => 'Gestion scolaire complète.', 'problem' => 'Les écoles gèrent tout sur papier.',
            'category_id' => $this->education->id, 'features' => "- Inscriptions\n- Bulletins de notes\n\n• Emplois du temps",
            'platforms' => ['web', 'android'], 'status' => 'disponible', 'sort_order' => 1, 'is_published' => '1', 'is_featured' => '1',
        ], $overrides);
    }

    public function test_the_gestionnaire_creates_and_publishes_a_software_with_its_visuals(): void
    {
        $this->actingAs($this->gestionnaire)->get('/admin/logiciels/create')->assertOk()->assertSee('IDENTIFICATION')
            ->assertSee('data-checklist', false)->assertSee('Enregistrer et publier')->assertSee('fiche-assistant.js');

        $this->post('/admin/logiciels', $this->payload([
            'logo_upload' => UploadedFile::fake()->image('logo.png', 300, 300),
            'gallery_upload' => [UploadedFile::fake()->image('ecran-1.jpg', 1200, 800), UploadedFile::fake()->image('ecran-2.jpg', 1200, 800)],
        ]))->assertRedirect('/admin/logiciels');

        $logiciel = Logiciel::with('gallery', 'logo')->firstOrFail();
        $this->assertSame('kalannet', $logiciel->slug);
        $this->assertSame(['Inscriptions', 'Bulletins de notes', 'Emplois du temps'], $logiciel->featureList());
        $this->assertTrue($logiciel->is_published);
        $this->assertNotNull($logiciel->published_at);
        $this->assertNotNull($logiciel->logo);
        $this->assertCount(2, $logiciel->gallery);
        $this->assertSame(3, Media::count());
        $this->assertTrue(ActivityLog::where('action', 'logiciels.published')->exists());

        $this->get('/logiciels')->assertOk()->assertSee('KalanNet');
        $this->get('/logiciels/kalannet')->assertOk()->assertSee('Bulletins de notes')->assertSee('Les écoles gèrent tout sur papier.')
            ->assertSee('Disponible')->assertSee('Sur devis')->assertSee('"@type":"SoftwareApplication"', false);
        $this->get('/')->assertSee('KalanNet');
    }

    public function test_validation_rules_of_the_software_sheet(): void
    {
        $inactive = Category::factory()->of(CategoryType::Logiciel)->create(['is_active' => false]);
        $materiel = Category::factory()->of(CategoryType::Materiel)->create();

        $this->actingAs($this->gestionnaire)->post('/admin/logiciels', $this->payload([
            'name' => '', 'summary' => '', 'category_id' => $inactive->id, 'status' => 'inconnu', 'platforms' => ['symbian'],
            'slug' => 'Mauvaise Adresse', 'video_url' => 'javascript:alert(1)', 'secondary_categories' => [$materiel->id],
        ]))->assertSessionHasErrors(['name', 'summary', 'category_id', 'status', 'platforms.0', 'slug', 'video_url', 'secondary_categories.0']);

        $this->post('/admin/logiciels', $this->payload(['category_id' => $materiel->id]))->assertSessionHasErrors('category_id');
        $this->assertSame(0, Logiciel::count());
    }

    public function test_a_draft_and_an_archived_software_stay_out_of_the_catalogue(): void
    {
        Logiciel::factory()->draft()->create(['name' => 'Brouillon Secret', 'slug' => 'brouillon-secret', 'category_id' => $this->education->id]);
        Logiciel::factory()->archived()->create(['name' => 'Ancien Logiciel', 'slug' => 'ancien-logiciel', 'category_id' => $this->education->id]);

        $this->get('/logiciels')->assertOk()->assertDontSee('Brouillon Secret');
        $this->assertStringNotContainsString('Ancien Logiciel', $this->resultsOf($this->get('/logiciels')->getContent()));
        $this->get('/logiciels/brouillon-secret')->assertNotFound();
        $this->get('/logiciels/ancien-logiciel')->assertOk()->assertSee('archivé')->assertDontSee('Demander une démonstration');
    }

    public function test_publication_toggles_from_the_list_and_is_audited(): void
    {
        $logiciel = Logiciel::factory()->create(['category_id' => $this->education->id]);

        $this->actingAs($this->gestionnaire)->post("/admin/logiciels/{$logiciel->id}/publication")->assertRedirect('/admin/logiciels');
        $this->assertFalse($logiciel->fresh()->is_published);
        $this->assertTrue(ActivityLog::where('action', 'logiciels.unpublished')->where('subject_id', $logiciel->id)->exists());

        $this->actingAs(User::factory()->commercial()->create())->post("/admin/logiciels/{$logiciel->id}/publication")->assertForbidden();
    }

    public function test_without_the_publish_permission_publication_and_archiving_are_kept(): void
    {
        $editor = User::factory()->gestionnaire()->create();
        $editor->roles->first()->revokePermissionTo('logiciels.publish');
        $logiciel = Logiciel::factory()->draft()->create(['category_id' => $this->education->id, 'status' => LogicielStatus::Disponible]);

        $this->actingAs($editor->fresh())->put("/admin/logiciels/{$logiciel->id}", $this->payload(['status' => 'archive', 'is_published' => '1']))
            ->assertRedirect('/admin/logiciels');

        $logiciel->refresh();
        $this->assertFalse($logiciel->is_published);
        $this->assertSame(LogicielStatus::Disponible, $logiciel->status);
        $this->assertSame('KalanNet', $logiciel->name);
    }

    public function test_the_catalogue_filters_by_sector_platform_and_search(): void
    {
        $commerce = Category::factory()->of(CategoryType::Logiciel)->create(['name' => 'Commerce']);
        Logiciel::factory()->create(['name' => 'KalanNet', 'category_id' => $this->education->id, 'platforms' => ['web']]);
        $jago = Logiciel::factory()->create(['name' => 'JAGO DANAYA', 'category_id' => $commerce->id, 'platforms' => ['android'], 'features' => ['Gestion de stock']]);
        $jago->secondaryCategories()->attach($this->education->id);

        $results = fn (string $uri) => $this->resultsOf($this->get($uri)->assertOk()->getContent());
        $this->assertStringContainsString('JAGO DANAYA', $results('/logiciels?categorie='.$commerce->slug));
        $this->assertStringNotContainsString('KalanNet', $results('/logiciels?categorie='.$commerce->slug));
        $this->assertStringContainsString('KalanNet', $results('/logiciels?categorie='.$this->education->slug));
        $this->assertStringContainsString('JAGO DANAYA', $results('/logiciels?categorie='.$this->education->slug));
        $this->assertStringContainsString('KalanNet', $results('/logiciels?plateforme=web'));
        $this->assertStringNotContainsString('JAGO DANAYA', $results('/logiciels?plateforme=web'));
        $this->assertStringContainsString('JAGO DANAYA', $results('/logiciels?q=stock'));
        $this->assertStringNotContainsString('KalanNet', $results('/logiciels?q=stock'));
        $this->get('/logiciels?q=introuvable')->assertOk()->assertSee('Aucun logiciel ne correspond');
    }

    public function test_a_category_in_use_is_deactivated_instead_of_deleted(): void
    {
        Logiciel::factory()->create(['category_id' => $this->education->id]);
        $free = Category::factory()->of(CategoryType::Logiciel)->create();
        $admin = User::factory()->administrateur()->create();

        $this->actingAs($admin)->get('/admin/categories/logiciel')->assertOk()->assertSee('Éducation et gestion scolaire');
        $this->delete("/admin/categories/element/{$this->education->id}")->assertSessionHas('error');
        $this->assertNotSoftDeleted($this->education);
        $this->delete("/admin/categories/element/{$free->id}")->assertSessionHas('status');
        $this->assertSoftDeleted($free);

        $this->post('/admin/categories/logiciel', ['name' => 'Santé', 'sort_order' => 5])->assertRedirect('/admin/categories/logiciel');
        $this->assertTrue(Category::ofType(CategoryType::Logiciel)->where('slug', 'sante')->exists());
        $this->actingAs(User::factory()->commercial()->create())->get('/admin/categories/logiciel')->assertForbidden();
    }

    /** Zone des résultats du catalogue (le menu de l'en-tête liste aussi les logiciels). */
    private function resultsOf(string $html): string
    {
        $start = strpos($html, 'id="catalogue-results"');

        return $start === false ? '' : substr($html, $start, (int) strpos($html, '</section>', $start) - $start);
    }
}
