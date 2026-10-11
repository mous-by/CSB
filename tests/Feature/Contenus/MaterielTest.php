<?php

namespace Tests\Feature\Contenus;

use App\Enums\Availability;
use App\Enums\CategoryType;
use App\Enums\MaterielCondition;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Materiel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Catalogue matériel (cahier §5.3, §5.4, REC-04, REC-05) : caractéristiques, état, prix facultatif, disponibilité
 * déclarée ; seuls les produits publiés sont visibles, filtrables et recherchables.
 */
class MaterielTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $gestionnaire;

    private Category $portables;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->syncPermissions();
        $this->gestionnaire = User::factory()->gestionnaire()->create();
        $this->portables = Category::factory()->of(CategoryType::Materiel)->create(['name' => 'Ordinateurs portables']);
    }

    public function test_the_gestionnaire_creates_a_product_with_specs_price_and_photos(): void
    {
        $this->actingAs($this->gestionnaire)->get('/admin/materiels/create')->assertOk()->assertSee('ÉTAT, PRIX ET DISPONIBILITÉ');

        $this->post('/admin/materiels', [
            'designation' => 'HP ProBook 450 G9', 'category_id' => $this->portables->id, 'brand' => 'HP', 'model' => 'ProBook 450 G9',
            'specs' => "Processeur : Intel Core i5\nMémoire : 8 Go\n\nStockage : SSD : 512 Go",
            'condition' => 'occasion', 'price' => '250 000', 'price_conditions' => 'Garantie 3 mois', 'availability' => 'disponible',
            'sort_order' => 0, 'is_published' => '1',
            'main_upload' => UploadedFile::fake()->image('probook.jpg', 1000, 800),
        ])->assertRedirect('/admin/materiels');

        $materiel = Materiel::firstOrFail();
        $this->assertSame('hp-probook-450-g9', $materiel->slug);
        $this->assertSame(250000, $materiel->price);
        $this->assertSame([['Processeur', 'Intel Core i5'], ['Mémoire', '8 Go'], ['Stockage', 'SSD : 512 Go']], $materiel->specList());
        $this->assertNotNull($materiel->main_media_id);
        $this->assertTrue(ActivityLog::where('action', 'materiels.published')->exists());

        $this->get('/materiel/hp-probook-450-g9')->assertOk()
            ->assertSee('250 000 FCFA')->assertSee('Garantie 3 mois')->assertSee('Occasion')->assertSee('Intel Core i5')
            ->assertSee('"priceCurrency":"XOF"', false)->assertSee('UsedCondition', false);
        $this->get('/')->assertSee('Ordinateurs portables')->assertSee('Voir les modèles');
    }

    public function test_a_product_without_price_shows_price_on_request_and_no_offer(): void
    {
        Materiel::factory()->create(['designation' => 'Imprimante laser', 'slug' => 'imprimante-laser', 'price' => null, 'category_id' => $this->portables->id]);

        $this->get('/materiel/imprimante-laser')->assertOk()->assertSee('Prix sur demande')->assertDontSee('"offers"', false);
    }

    public function test_validation_of_the_product_sheet(): void
    {
        $this->actingAs($this->gestionnaire)->post('/admin/materiels', [
            'designation' => '', 'category_id' => Category::factory()->of(CategoryType::Logiciel)->create()->id,
            'specs' => 'Processeur Intel sans deux-points', 'condition' => 'abime', 'price' => '12,5', 'availability' => 'peut-etre', 'sort_order' => 0,
            'main_upload' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors(['designation', 'category_id', 'specs.0.value', 'condition', 'price', 'availability', 'main_upload']);

        $this->assertSame(0, Materiel::count());
    }

    public function test_a_promotion_strikes_the_normal_price_until_its_end_date(): void
    {
        $this->actingAs($this->gestionnaire)->post('/admin/materiels', [
            'designation' => 'Dell OptiPlex 7040', 'category_id' => $this->portables->id, 'condition' => 'occasion', 'availability' => 'disponible',
            'price' => '250 000', 'promo_price' => '210 000', 'promo_ends_on' => today()->addDays(5)->toDateString(), 'sort_order' => 0, 'is_published' => '1',
        ])->assertRedirect('/admin/materiels');

        $materiel = Materiel::sole();
        $this->assertTrue($materiel->isOnPromo());
        $this->assertSame(210000, $materiel->currentPrice());
        $this->assertSame(16, $materiel->promoPercent());

        $this->get('/materiel/dell-optiplex-7040')->assertOk()
            ->assertSee('<del class="price-old"', false)->assertSee('250 000 FCFA')->assertSee('210 000 FCFA')->assertSee('-16 %')
            ->assertSee('"price":"210000"', false);
        $this->get('/materiel')->assertSee('Promo -16 %');
        $this->get('/')->assertSee('Promotions et matériel du moment')->assertSee('210 000 FCFA');
        $this->get('/admin/materiels')->assertSee('En promotion');

        // Promotion terminée : seul le prix normal reste, sans rature.
        $this->travel(6)->days();
        $this->assertFalse($materiel->fresh()->isOnPromo());
        $this->assertSame(0, Materiel::onPromo()->count());
        $this->get('/materiel/dell-optiplex-7040')->assertSee('250 000 FCFA')->assertDontSee('210 000 FCFA')->assertDontSee('<del class="price-old"', false);
    }

    public function test_a_promotional_price_must_be_lower_than_the_normal_price(): void
    {
        $base = ['designation' => 'HP 250', 'category_id' => $this->portables->id, 'condition' => 'neuf', 'availability' => 'disponible', 'sort_order' => 0];

        $this->actingAs($this->gestionnaire)->post('/admin/materiels', $base + ['price' => '200 000', 'promo_price' => '200 000'])->assertSessionHasErrors('promo_price');
        $this->post('/admin/materiels', $base + ['promo_price' => '150 000'])->assertSessionHasErrors('promo_price');
        $this->post('/admin/materiels', $base + ['price' => '200 000', 'promo_ends_on' => '2026-12-31'])->assertSessionHasErrors('promo_price');
        $this->assertSame(0, Materiel::count());
    }

    public function test_the_catalogue_filters_and_hides_unpublished_products(): void
    {
        $imprimantes = Category::factory()->of(CategoryType::Materiel)->create(['name' => 'Imprimantes']);
        Materiel::factory()->create(['designation' => 'Portable Neuf', 'category_id' => $this->portables->id, 'condition' => MaterielCondition::Neuf]);
        Materiel::factory()->create(['designation' => 'Portable Occasion', 'category_id' => $this->portables->id, 'condition' => MaterielCondition::Occasion, 'availability' => Availability::Indisponible]);
        Materiel::factory()->create(['designation' => 'Imprimante Canon', 'brand' => 'Canon', 'category_id' => $imprimantes->id]);
        Materiel::factory()->draft()->create(['designation' => 'Produit Brouillon', 'slug' => 'produit-brouillon', 'category_id' => $this->portables->id]);

        $this->get('/materiel')->assertOk()->assertSee('Portable Neuf')->assertSee('Imprimante Canon')->assertDontSee('Produit Brouillon');
        $this->get('/materiel?categorie='.$imprimantes->slug)->assertSee('Imprimante Canon')->assertDontSee('Portable Neuf');
        $this->get('/materiel?etat=occasion')->assertSee('Portable Occasion')->assertDontSee('Portable Neuf');
        $this->get('/materiel?disponibilite=indisponible')->assertSee('Portable Occasion')->assertDontSee('Imprimante Canon');
        $this->get('/materiel?q=canon')->assertSee('Imprimante Canon')->assertDontSee('Portable Neuf');
        $this->get('/materiel?q=rien-du-tout')->assertSee('Aucun produit ne correspond');
        $this->get('/materiel/produit-brouillon')->assertNotFound();
    }

    public function test_the_administrateur_updates_availability_and_unpublishes(): void
    {
        $materiel = Materiel::factory()->create(['category_id' => $this->portables->id]);
        $admin = User::factory()->administrateur()->create();

        $this->actingAs($admin)->put("/admin/materiels/{$materiel->id}", [
            'designation' => $materiel->designation, 'category_id' => $this->portables->id, 'condition' => 'neuf',
            'availability' => 'indisponible', 'sort_order' => 0, 'is_published' => '1',
        ])->assertRedirect('/admin/materiels');
        $this->assertSame(Availability::Indisponible, $materiel->fresh()->availability);

        $this->post("/admin/materiels/{$materiel->id}/publication")->assertRedirect('/admin/materiels');
        $this->assertFalse($materiel->fresh()->is_published);
        $this->get('/admin/materiels')->assertOk()->assertSee('CATALOGUE DE MATÉRIEL');

        $this->actingAs(User::factory()->commercial()->create())->get('/admin/materiels')->assertForbidden();
    }
}
