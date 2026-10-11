<?php

namespace Tests\Feature\Contenus;

use App\Enums\CategoryType;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Materiel;
use App\Models\Prestation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Saisie en masse (demande de CSB : « ajouter des lignes » et « ajouter un fichier ») : un seul enregistrement crée
 * plusieurs éléments, en tout ou rien, avec les mêmes règles qu'une fiche, audité.
 */
class BulkEntryTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->gestionnaire = User::factory()->gestionnaire()->create();
    }

    public function test_several_products_are_created_in_one_save(): void
    {
        $portables = Category::factory()->of(CategoryType::Materiel)->create(['name' => 'Portables']);

        $this->actingAs($this->gestionnaire)->get('/admin/materiels/lot')->assertOk()->assertSee('SAISIE EN LOT — PRODUITS', false)->assertSee('Portables');

        $this->post('/admin/materiels/lot', ['rows' => [
            ['designation' => 'HP 250 G8', 'category_id' => $portables->id, 'condition' => 'neuf', 'price' => '250 000', 'availability' => 'disponible', 'specs' => "Processeur : i5\nMémoire : 8 Go", 'is_published' => '1'],
            ['designation' => 'Dell Latitude 5490', 'category_id' => 'Portables', 'condition' => 'Occasion', 'availability' => 'Sur demande', 'is_published' => '0'],
            ['designation' => '', 'category_id' => '', 'condition' => '', 'availability' => '', 'is_published' => '0'], // ligne vide : ignorée
        ]])->assertRedirect('/admin/materiels')->assertSessionHas('status', '2 produits enregistré(e)s en une fois.');

        $this->assertSame(2, Materiel::count());
        $hp = Materiel::where('designation', 'HP 250 G8')->firstOrFail();
        $this->assertSame(250000, $hp->price);
        $this->assertSame([['Processeur', 'i5'], ['Mémoire', '8 Go']], $hp->specList());
        $this->assertTrue($hp->is_published);
        $this->assertSame('occasion', Materiel::where('designation', 'Dell Latitude 5490')->value('condition')->value);
        $this->assertTrue(ActivityLog::where('action', 'materiels.bulk_created')->exists());
    }

    public function test_one_bad_row_rejects_the_whole_batch_with_errors_per_cell(): void
    {
        $category = Category::factory()->of(CategoryType::Materiel)->create();

        $this->actingAs($this->gestionnaire)->from('/admin/materiels/lot')->post('/admin/materiels/lot', ['rows' => [
            ['designation' => 'Produit valide', 'category_id' => $category->id, 'condition' => 'neuf', 'availability' => 'disponible'],
            ['designation' => 'Produit valide', 'category_id' => 999, 'condition' => 'abime', 'availability' => 'disponible', 'price' => 'cher', 'specs' => 'sans deux points'],
        ]])->assertRedirect('/admin/materiels/lot')
            ->assertSessionHasErrors(['rows.1.designation', 'rows.1.category_id', 'rows.1.condition', 'rows.1.price', 'rows.1.specs.0']);

        $this->assertSame(0, Materiel::count());
        $this->post('/admin/materiels/lot', ['rows' => []])->assertSessionHasErrors('rows');
    }

    public function test_an_excel_file_is_imported_all_or_nothing(): void
    {
        $this->actingAs($this->gestionnaire);

        $template = $this->get('/admin/prestations/import/modele')->assertOk()->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents($path, $template);
        $book = IOFactory::load($path);
        $this->assertSame(['Titre *', 'Icône', 'Résumé *', 'Ordre', 'Publié'], array_slice($book->getSheet(0)->toArray()[0], 0, 5));
        $this->assertNotNull($book->getSheetByName('Mode d\'emploi'));

        $this->post('/admin/prestations/import', ['fichier' => $this->xlsx([
            ['Titre', 'Résumé', 'Icône', 'Publié'],
            ['Installation réseau', 'Câblage et Wi-Fi des bureaux.', 'Réseau', 'oui'],
            ['Formation bureautique', 'Word, Excel et Internet pour vos équipes.', 'bx-chalkboard', 'non'],
        ])])->assertRedirect('/admin/prestations')->assertSessionHas('status', 'Import réussi : 2 services créé(e)s.');
        $this->assertSame('bx-network-chart', Prestation::where('title', 'Installation réseau')->value('icon'));
        $this->assertFalse(Prestation::where('title', 'Formation bureautique')->value('is_published'));

        $this->post('/admin/prestations/import', ['fichier' => $this->xlsx([
            ['Titre', 'Résumé'],
            ['Service OK', 'Résumé OK'],
            ['', 'Titre manquant'],
        ])])->assertRedirect('/admin/prestations/lot')->assertSessionHas('import_errors', fn ($errors) => $errors[0]['line'] === 3 && $errors[0]['column'] === 'Titre');
        $this->assertSame(2, Prestation::count(), 'Rien n\'est importé quand une ligne est en erreur.');

        $this->post('/admin/prestations/import', ['fichier' => UploadedFile::fake()->create('virus.exe', 5)])->assertSessionHasErrors('fichier');
    }

    public function test_categories_are_checked_family_by_family(): void
    {
        $editor = User::factory()->gestionnaire()->create();
        $editor->roles->first()->revokePermissionTo('articles.update');

        $this->actingAs($editor->fresh())->post('/admin/categories/lot', ['rows' => [
            ['type' => 'logiciel', 'name' => 'Santé'],
            ['type' => 'article', 'name' => 'Promotions'],
        ]])->assertSessionHasErrors('rows.1');
        $this->assertSame(0, Category::count());

        $this->post('/admin/categories/lot', ['rows' => [['type' => 'logiciel', 'name' => 'Santé'], ['type' => 'logiciel', 'name' => 'santé']]])->assertSessionHasErrors('rows.1.name');
    }

    public function test_the_commercial_cannot_use_bulk_entry(): void
    {
        $this->actingAs(User::factory()->commercial()->create());
        $this->get('/admin/materiels/lot')->assertForbidden();
        $this->post('/admin/logiciels/lot', ['rows' => [['name' => 'X']]])->assertForbidden();
    }

    /** @param list<list<string>> $rows */
    private function xlsx(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);

        return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
