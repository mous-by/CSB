<?php

namespace Tests\Feature\Contenus;

use App\Enums\CategoryType;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Category;
use App\Models\Logiciel;
use App\Models\Realisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Réalisations et actualités (cahier §4, §7) : rédiger, prévisualiser, publier, programmer, dépublier, catégoriser.
 */
class RealisationArticleTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->syncPermissions();
        $this->gestionnaire = User::factory()->gestionnaire()->create();
    }

    public function test_a_project_is_created_with_technologies_and_a_link_to_a_software(): void
    {
        $logiciel = Logiciel::factory()->create(['name' => 'KalanNet', 'slug' => 'kalannet']);

        $this->actingAs($this->gestionnaire)->get('/admin/realisations/create')->assertOk();
        $this->post('/admin/realisations', [
            'title' => 'Informatisation du lycée de Kati', 'client_name' => 'Lycée de Kati', 'sector' => 'Éducation', 'year' => 2025,
            'summary' => 'Déploiement de KalanNet.', 'context' => 'Gestion papier.', 'need' => 'Bulletins rapides.', 'solution' => '## Déploiement\n\nFormation des enseignants.',
            'technologies' => "Laravel, MySQL\nAndroid", 'logiciel_id' => $logiciel->id, 'sort_order' => 0, 'is_published' => '1',
        ])->assertRedirect('/admin/realisations');

        $realisation = Realisation::firstOrFail();
        $this->assertSame(['Laravel', 'MySQL', 'Android'], $realisation->technologyList());
        $this->assertTrue(ActivityLog::where('action', 'realisations.published')->exists());

        $this->get('/realisations')->assertOk()->assertSee('Informatisation du lycée de Kati');
        $this->get('/realisations?secteur=Éducation')->assertSee('Informatisation du lycée de Kati');
        $this->get('/realisations?secteur=Santé')->assertSee('Informatisation du lycée de Kati', false); // secteur inconnu : ignoré
        $this->get('/realisations/'.$realisation->slug)->assertOk()->assertSee('Lycée de Kati')->assertSee('Android')->assertSee('Découvrir KalanNet');
        $this->get('/')->assertSee('Informatisation du lycée de Kati');
    }

    public function test_an_unpublished_project_stays_private(): void
    {
        $draft = Realisation::factory()->draft()->create(['title' => 'Projet Confidentiel']);

        $this->get('/realisations')->assertDontSee('Projet Confidentiel');
        $this->get('/realisations/'.$draft->slug)->assertNotFound();
    }

    public function test_an_article_is_written_previewed_scheduled_then_published(): void
    {
        $conseils = Category::factory()->of(CategoryType::Article)->create(['name' => 'Conseils']);
        $this->actingAs($this->gestionnaire);

        $this->post('/admin/articles', [
            'title' => 'Bien choisir son ordinateur portable', 'body' => "## Les critères\n\nLe **processeur** compte.", 'category_id' => $conseils->id,
            'is_published' => '1', 'published_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])->assertRedirect('/admin/articles')->assertSessionHas('status', fn ($s) => str_contains($s, 'programmé'));

        $article = Article::firstOrFail();
        $this->assertSame($this->gestionnaire->id, $article->author_id);
        $this->assertTrue($article->isScheduled());

        $this->get('/actualites')->assertDontSee('Bien choisir son ordinateur');
        $this->get('/actualites/'.$article->slug)->assertNotFound();
        $this->get("/admin/articles/{$article->id}/apercu")->assertOk()->assertSee('Aperçu')->assertSee('<strong>processeur</strong>', false)->assertSee('noindex', false);
        $html = $this->postJson('/admin/articles/markdown', ['body' => '**gras** <script>x</script>'])->assertOk()->json('html');
        $this->assertStringContainsString('<strong>gras</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);

        $article->update(['published_at' => now()->subMinute()]);
        $this->get('/actualites')->assertSee('Bien choisir son ordinateur');
        $this->get('/actualites?categorie='.$conseils->slug)->assertSee('Bien choisir son ordinateur');
        $this->get('/actualites/'.$article->slug)->assertOk()->assertSee('min de lecture')->assertSee('"@type":"Article"', false);
        $this->get('/')->assertSee('Bien choisir son ordinateur');
    }

    public function test_article_rules_and_permissions(): void
    {
        $this->actingAs($this->gestionnaire)->post('/admin/articles', ['title' => '', 'body' => '', 'category_id' => Category::factory()->of(CategoryType::Logiciel)->create()->id])
            ->assertSessionHasErrors(['title', 'body', 'category_id']);

        $article = Article::factory()->create();
        $this->actingAs(User::factory()->commercial()->create());
        $this->get('/admin/articles')->assertForbidden();
        $this->get("/admin/articles/{$article->id}/apercu")->assertForbidden();
        $this->post("/admin/articles/{$article->id}/publication")->assertForbidden();

        $this->actingAs($this->gestionnaire)->post("/admin/articles/{$article->id}/publication")->assertRedirect('/admin/articles');
        $this->assertFalse($article->fresh()->is_published);
        $this->assertTrue(ActivityLog::where('action', 'articles.unpublished')->exists());
    }
}
