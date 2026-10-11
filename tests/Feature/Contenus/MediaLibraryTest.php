<?php

namespace Tests\Feature\Contenus;

use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Prestation;
use App\Models\User;
use App\Services\Medias\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Bibliothèque d'images (cahier §7, §9, §10, REC-09) : type réel, taille, nom généré, redimensionnement, vignette,
 * image utilisée non supprimable.
 */
class MediaLibraryTest extends TestCase
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

    public function test_an_image_is_stored_under_a_generated_name_resized_with_a_thumbnail(): void
    {
        $this->actingAs($this->gestionnaire)->post('/admin/medias', [
            'fichiers' => [UploadedFile::fake()->image('Capture écran KalanNet.png', 2400, 1200)],
            'folder' => 'Logiciels', 'alt' => 'Écran d\'accueil',
        ])->assertRedirect('/admin/medias')->assertSessionHas('status');

        $media = Media::firstOrFail();
        $this->assertSame('capture-ecran-kalannet.png', $media->original_name);
        $this->assertMatchesRegularExpression('#^medias/\d{4}/\d{2}/[0-9a-f-]{36}\.png$#', $media->path);
        $this->assertSame([1600, 800], [$media->width, $media->height]);
        Storage::disk('public')->assertExists([$media->path, $media->thumb_path]);
        $this->assertSame(480, getimagesizefromstring(Storage::disk('public')->get($media->thumb_path))[0]);
        $this->assertSame($this->gestionnaire->id, $media->uploaded_by);
        $this->assertTrue(ActivityLog::where('action', 'medias.created')->exists());
    }

    public function test_forbidden_or_oversized_files_are_refused_server_side(): void
    {
        $this->actingAs($this->gestionnaire);

        // Un PDF renommé en .jpg : le type réel est vérifié, pas l'extension.
        $fake = UploadedFile::fake()->createWithContent('photo.jpg', "%PDF-1.4\n1 0 obj << >> endobj\n%%EOF");
        $this->post('/admin/medias', ['fichiers' => [$fake]])->assertSessionHasErrors('fichiers.0');

        $this->post('/admin/medias', ['fichiers' => [UploadedFile::fake()->create('script.php', 10, 'text/x-php')]])->assertSessionHasErrors('fichiers.0');
        $this->post('/admin/medias', ['fichiers' => [UploadedFile::fake()->image('enorme.jpg')->size(6000)]])->assertSessionHasErrors('fichiers.0');
        $this->post('/admin/medias', ['fichiers' => []])->assertSessionHasErrors('fichiers');

        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_commercial_cannot_upload(): void
    {
        $this->actingAs(User::factory()->commercial()->create())
            ->post('/admin/medias', ['fichiers' => [UploadedFile::fake()->image('a.jpg')]])->assertForbidden();
    }

    public function test_an_image_used_by_a_content_cannot_be_deleted(): void
    {
        $media = app(MediaService::class)->store(UploadedFile::fake()->image('a.jpg', 800, 600), $this->gestionnaire);
        $prestation = Prestation::factory()->create();
        app(MediaService::class)->syncGallery($prestation, [$media->id]);

        $admin = User::factory()->administrateur()->create();
        $this->actingAs($admin)->delete("/admin/medias/{$media->id}")->assertSessionHas('error');
        $this->assertNotSoftDeleted($media);

        app(MediaService::class)->syncGallery($prestation, []);
        $this->delete("/admin/medias/{$media->id}")->assertSessionHas('status');
        $this->assertSoftDeleted($media);
    }

    public function test_galleries_keep_the_chosen_order(): void
    {
        $service = app(MediaService::class);
        [$a, $b, $c] = Media::factory()->count(3)->create()->all();
        $prestation = Prestation::factory()->create();

        $service->syncGallery($prestation, [$c->id, $a->id, 999999]);
        $service->syncGallery($prestation, [$b->id, $c->id, $a->id]);

        $this->assertSame([$b->id, $c->id, $a->id], DB::table('mediables')->where('mediable_id', $prestation->id)->orderBy('position')->pluck('media_id')->all());
    }

    public function test_the_search_feeds_the_image_pickers(): void
    {
        Media::factory()->create(['original_name' => 'logo-kalannet.png', 'alt' => 'Logo KalanNet']);
        Media::factory()->create(['original_name' => 'imprimante.jpg', 'alt' => 'Imprimante laser']);

        $this->actingAs($this->gestionnaire)->getJson('/admin/medias/search?q=kalan')->assertOk()
            ->assertJsonCount(1, 'results')->assertJsonPath('results.0.text', 'Logo KalanNet');
        $this->actingAs(User::factory()->commercial()->create())->getJson('/admin/medias/search')->assertForbidden();
    }

    public function test_the_library_screen_lists_the_images(): void
    {
        Media::factory()->create(['original_name' => 'photo-boutique.jpg', 'alt' => null]);

        $this->actingAs($this->gestionnaire)->get('/admin/medias')->assertOk()
            ->assertSee('BIBLIOTHÈQUE D\'IMAGES', false)->assertSee('photo-boutique.jpg')->assertSee('Sans texte alternatif');
    }
}
