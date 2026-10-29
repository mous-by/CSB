<?php

namespace Tests\Feature\Modules;

use App\Mail\TemoignageMerci;
use App\Mail\TemoignageRecu;
use App\Models\ActivityLog;
use App\Models\Temoignage;
use App\Models\User;
use App\Services\Videos\ChunkedVideoUpload;
use App\Services\Videos\TestimonialVideoService;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Témoignages en vidéo (demande de CSB, 2026-10-12) : envoi par morceaux (type réel vérifié), formulaire public modéré,
 * conversion (ffmpeg simulé), page publique, administration (vidéo, lien, photo, note), permissions.
 */
class TemoignageVideoTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        RateLimiter::clear('demandes-min|127.0.0.1');
    }

    /** Envoie le fichier par morceaux de 1 Mo comme le navigateur ; renvoie le jeton. */
    private function uploadInChunks(string $url, string $contents): ?string
    {
        $id = (string) Str::uuid();
        $chunks = str_split($contents, ChunkedVideoUpload::CHUNK_SIZE) ?: [''];
        $token = null;
        foreach ($chunks as $index => $chunk) {
            $response = $this->post($url, ['upload_id' => $id, 'index' => $index, 'total' => count($chunks), 'chunk' => UploadedFile::fake()->createWithContent('chunk', $chunk)], ['Accept' => 'application/json']);
            $response->assertOk();
            $token = $response->json('token');
        }

        return $token;
    }

    private function fixture(): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/temoignage.mp4'));
    }

    public function test_a_client_sends_a_video_testimonial_which_waits_for_moderation(): void
    {
        Mail::fake();
        Process::fake(['*-version*' => Process::result('ffmpeg version 6'), '*ffprobe*' => Process::result('83.4'), '*' => Process::result()]);
        config(['site.notification_email' => 'equipe@csb.test']);

        $this->get('/temoignages/partager')->assertOk()->assertSee('data-video-upload', false);
        $token = $this->uploadInChunks('/temoignages/video', $this->fixture());
        $this->assertNotNull($token);

        $this->post('/temoignages/partager', [
            'author_name' => 'Aminata Coulibaly', 'organization' => 'Groupe scolaire Les Étoiles', 'contact' => '70 11 22 33', 'rating' => 5,
            'video_token' => $token, 'consent' => '1', SpamGuard::TOKEN => Crypt::encryptString((string) now()->subSeconds(20)->getTimestamp()),
        ])->assertRedirect('/temoignages/partager')->assertSessionHas('temoignage_sent');

        $temoignage = Temoignage::sole();
        $this->assertSame('client', $temoignage->source);
        $this->assertFalse($temoignage->is_published);
        $this->assertNotNull($temoignage->consent_at);
        $this->assertSame(Temoignage::VIDEO_READY, $temoignage->video_status, 'Conversion faite (file synchrone en test).');
        $this->assertStringEndsWith('.mp4', $temoignage->video_path);
        $this->assertSame(83, $temoignage->video_duration);
        Mail::assertSent(TemoignageRecu::class, fn ($m) => $m->hasTo('equipe@csb.test'));
        Mail::assertNotSent(TemoignageMerci::class); // contact = téléphone : pas d'e-mail de remerciement
        $this->assertTrue(ActivityLog::where('action', 'temoignages.submitted')->exists());

        // Pas publié tant que l'équipe ne l'a pas validé.
        $this->get('/temoignages')->assertOk()->assertDontSee('Aminata Coulibaly');
        $temoignage->update(['is_published' => true]);
        $this->get('/temoignages')->assertSee('Aminata Coulibaly')->assertSee('data-video-play', false)->assertSee('1:23');
        $this->get('/')->assertSee('Aminata Coulibaly');
    }

    public function test_a_written_testimonial_needs_text_and_consent_and_contact_stays_private(): void
    {
        Mail::fake();
        $human = [SpamGuard::TOKEN => Crypt::encryptString((string) now()->subSeconds(20)->getTimestamp())];

        $this->post('/temoignages/partager', ['author_name' => 'Oumar', 'contact' => '', 'content' => ''] + $human)->assertSessionHasErrors(['contact', 'content', 'consent']);
        $this->post('/temoignages/partager', ['author_name' => 'Oumar Traoré', 'contact' => 'oumar@exemple.ml', 'content' => 'Très bon service, rapide et sérieux.', 'consent' => '1'] + $human)->assertSessionHasNoErrors();

        // Contact = e-mail : l'auteur est remercié (publication après relecture).
        Mail::assertSent(TemoignageMerci::class, fn (TemoignageMerci $m) => $m->hasTo('oumar@exemple.ml') && str_contains($m->render(), 'relit votre témoignage'));

        Temoignage::sole()->update(['is_published' => true]);
        $this->get('/temoignages')->assertSee('Très bon service')->assertDontSee('oumar@exemple.ml');
    }

    public function test_a_testimonial_to_moderate_is_validated_in_one_click(): void
    {
        $this->syncPermissions();
        $temoignage = Temoignage::create(['author_name' => 'Kadiatou Sow', 'contact' => '70 00 00 00', 'content' => 'Service rapide.', 'source' => 'client', 'consent_at' => now(), 'is_published' => false, 'sort_order' => 0]);
        $this->assertTrue($temoignage->awaitingModeration());

        // Lecture seule (communication) : ni bouton ni validation.
        $this->actingAs(User::factory()->communication()->create())->get('/admin/temoignages')->assertOk()->assertDontSee('<span>Valider</span></button>', false);
        $this->post("/admin/temoignages/{$temoignage->id}/publication")->assertForbidden();

        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin/temoignages')->assertOk()
            ->assertSee('<span>Valider</span></button>', false)->assertSee('Afficher seulement ceux à valider');
        $this->post("/admin/temoignages/{$temoignage->id}/publication")->assertRedirect('/admin/temoignages')->assertSessionHas('status', fn ($s) => str_contains($s, 'validé'));

        $this->assertTrue($temoignage->fresh()->is_published);
        $this->assertTrue(ActivityLog::where('action', 'temoignages.published')->exists());
        $this->get('/temoignages')->assertSee('Kadiatou Sow');

        // Un second clic le masque.
        $this->post("/admin/temoignages/{$temoignage->id}/publication")->assertSessionHas('status', fn ($s) => str_contains($s, 'masqué'));
        $this->assertFalse($temoignage->fresh()->is_published);

        // Vidéo pas encore prête ou illisible : validé quand même, avec un avertissement qui reste affiché.
        $temoignage->refresh()->forceFill(['video_path' => 'temoignages/videos/x.mov', 'video_status' => Temoignage::VIDEO_PROCESSING])->saveQuietly();
        $this->post("/admin/temoignages/{$temoignage->id}/publication")->assertSessionHas('warning', fn ($s) => str_contains($s, 'encore en préparation'));
        $temoignage->refresh()->forceFill(['is_published' => false, 'video_status' => Temoignage::VIDEO_FAILED, 'video_error' => 'ffmpeg absent'])->saveQuietly();
        $this->post("/admin/temoignages/{$temoignage->id}/publication")->assertSessionHas('warning', fn ($s) => str_contains($s, 'illisible') && str_contains($s, 'MP4'));
        $this->assertTrue($temoignage->fresh()->is_published);
    }

    public function test_the_video_address_follows_the_visited_site_not_app_url(): void
    {
        // APP_URL faux sur le serveur (http, autre domaine) : la vidéo reste lisible, comme les images (asset()).
        config(['app.url' => 'http://localhost', 'filesystems.disks.public.url' => 'http://localhost/storage']);
        Temoignage::create(['author_name' => 'Issa Traoré', 'content' => 'Très bien.', 'source' => 'client', 'contact' => 'x', 'consent_at' => now(), 'is_published' => true, 'sort_order' => 0,
            'video_path' => 'temoignages/videos/pi.mp4', 'video_status' => Temoignage::VIDEO_READY]);

        $this->get('https://computerservicebarry.com/temoignages')->assertOk()
            ->assertSee('data-video-play="https://computerservicebarry.com/storage/temoignages/videos/pi.mp4"', false)
            ->assertSee('class="vt-frame"', false) // pas d'image d'aperçu : l'image de la vidéo sert d'aperçu
            ->assertDontSee('http://localhost/storage', false);
    }

    public function test_without_ffmpeg_an_h264_mov_is_renamed_mp4_and_hevc_is_refused_but_still_watchable_by_the_team(): void
    {
        $this->syncPermissions();
        Storage::fake('public');
        Process::fake(['*' => Process::result(exitCode: 127)]);
        $service = app(TestimonialVideoService::class);
        $make = fn (string $name, string $path) => Temoignage::create(['author_name' => $name, 'content' => 'Bien.', 'source' => 'client', 'contact' => 'x', 'consent_at' => now(),
            'is_published' => false, 'sort_order' => 0, 'video_path' => $path, 'video_status' => Temoignage::VIDEO_PROCESSING]);

        // .mov d'un téléphone en H.264 : même conteneur qu'un MP4, renommé et publié tel quel.
        Storage::disk('public')->put('temoignages/videos/a.mov', $this->fixture());
        $h264 = $make('Awa', 'temoignages/videos/a.mov');
        $service->process($h264);
        $h264->refresh();
        $this->assertSame(Temoignage::VIDEO_READY, $h264->video_status);
        $this->assertSame('temoignages/videos/a.mp4', $h264->video_path);
        Storage::disk('public')->assertExists('temoignages/videos/a.mp4');
        Storage::disk('public')->assertMissing('temoignages/videos/a.mov');

        // .mov en HEVC (iPhone « Haute efficacité ») : refusé avec une explication, mais l'équipe peut le regarder.
        Storage::disk('public')->put('temoignages/videos/b.mov', "\0\0\0\x20ftypqt  ".str_repeat("\0", 16)."\0\0\0\x40stsd\0\0\0\0\0\0\0\x01\0\0\0\x30hvc1".str_repeat("\0", 40));
        $hevc = $make('Bakary', 'temoignages/videos/b.mov');
        $service->process($hevc);
        $hevc->refresh();
        $this->assertSame(Temoignage::VIDEO_FAILED, $hevc->video_status);
        $this->assertStringContainsString('HEVC', $hevc->video_error);

        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin/temoignages')->assertOk()
            ->assertSee('Ouvrir la vidéo')->assertSee('Télécharger la vidéo')->assertSee('storage/temoignages/videos/b.mov#t=1', false)->assertSee('Pas affichée sur le site');
    }

    public function test_a_non_video_file_is_refused_and_chunks_must_arrive_in_order(): void
    {
        $id = (string) Str::uuid();
        $this->postJson('/temoignages/video', ['upload_id' => $id, 'index' => 1, 'total' => 2, 'chunk' => UploadedFile::fake()->createWithContent('c', 'x')])->assertUnprocessable();
        $this->postJson('/temoignages/video', ['upload_id' => $id, 'index' => 0, 'total' => 1, 'chunk' => UploadedFile::fake()->createWithContent('c', '<?php echo "pirate";')])
            ->assertUnprocessable()->assertJsonValidationErrors('video');
        $this->assertSame([], Storage::disk('local')->files(ChunkedVideoUpload::DIRECTORY));
    }

    public function test_without_ffmpeg_an_mp4_is_published_as_is(): void
    {
        $this->syncPermissions();
        Process::fake(['*' => Process::result(exitCode: 127)]);
        // Hébergement sans ffmpeg et dont le cron ne vide pas la file : la vidéo est prête sans passer par la file.
        Queue::fake();
        $this->actingAs(User::factory()->gestionnaire()->create());

        $token = $this->uploadInChunks('/admin/temoignages/video', $this->fixture());
        $this->post('/admin/temoignages', ['author_name' => 'Seydou Diarra', 'video_token' => $token, 'sort_order' => 0, 'is_published' => '1'])->assertRedirect('/admin/temoignages');

        $temoignage = Temoignage::sole();
        $this->assertSame(Temoignage::VIDEO_READY, $temoignage->video_status);
        $this->assertNull($temoignage->video_poster_path);
        Queue::assertNothingPushed();
        Storage::disk('public')->assertExists($temoignage->video_path);
        $this->get('/admin/temoignages')->assertOk()->assertSee('Seydou Diarra');
    }

    public function test_the_team_adds_a_video_link_photo_and_rating_and_removes_a_video(): void
    {
        $this->syncPermissions();
        Process::fake(['*' => Process::result(exitCode: 127)]);
        $user = User::factory()->gestionnaire()->create();
        $this->actingAs($user);

        $this->post('/admin/temoignages', ['author_name' => 'Fanta', 'sort_order' => 0])->assertSessionHasErrors('content');
        $this->post('/admin/temoignages', ['author_name' => 'Fanta', 'sort_order' => 0, 'video_url' => 'javascript:alert(1)'])->assertSessionHasErrors('video_url');

        $this->post('/admin/temoignages', [
            'author_name' => 'Fanta Keïta', 'organization' => 'Boutique Keïta', 'rating' => 4, 'video_url' => 'https://www.youtube.com/watch?v=abc123',
            'photo_upload' => UploadedFile::fake()->image('fanta.jpg', 400, 400), 'sort_order' => 0, 'is_published' => '1', 'is_featured' => '1',
        ])->assertRedirect('/admin/temoignages');
        $temoignage = Temoignage::sole();
        $this->assertSame(4, $temoignage->rating);
        $this->assertNotNull($temoignage->photo_media_id);
        $this->assertTrue($temoignage->is_featured);
        $this->get('/temoignages')->assertSee('YouTube')->assertSee('https://www.youtube.com/watch?v=abc123', false);

        $token = $this->uploadInChunks('/admin/temoignages/video', $this->fixture());
        $this->put("/admin/temoignages/{$temoignage->id}", ['author_name' => 'Fanta Keïta', 'video_token' => $token, 'sort_order' => 0, 'is_published' => '1'])->assertRedirect();
        $path = $temoignage->fresh()->video_path;
        Storage::disk('public')->assertExists($path);

        $this->put("/admin/temoignages/{$temoignage->id}", ['author_name' => 'Fanta Keïta', 'content' => 'Merci CSB.', 'remove_video' => '1', 'sort_order' => 0])->assertRedirect();
        $this->assertNull($temoignage->fresh()->video_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_the_admin_upload_endpoint_requires_the_permission_and_old_uploads_are_cleaned(): void
    {
        $this->syncPermissions();
        $this->actingAs(User::factory()->commercial()->create())->postJson('/admin/temoignages/video', [])->assertForbidden();

        Storage::disk('local')->put(ChunkedVideoUpload::DIRECTORY.'/vieux.part', 'x');
        touch(Storage::disk('local')->path(ChunkedVideoUpload::DIRECTORY.'/vieux.part'), now()->subDays(2)->getTimestamp());
        $this->artisan('videos:clean')->assertSuccessful();
        Storage::disk('local')->assertMissing(ChunkedVideoUpload::DIRECTORY.'/vieux.part');
    }
}
