<?php

namespace Tests\Feature\Demandes;

use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Enums\LogicielStatus;
use App\Mail\DemandeAccuse;
use App\Mail\NouvelleDemande;
use App\Models\ActivityLog;
use App\Models\Demande;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Formulaires du site (cahier §6, REC-06, REC-07, REC-09, §10 anti-spam) : enregistrement, validation, anti-spam,
 * pièce jointe, notification e-mail (un échec ne perd pas la demande).
 */
class DemandeFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        RateLimiter::clear('demandes-min|127.0.0.1');
        config(['site.notification_email' => 'equipe@csb.test']);
    }

    /** @return array<string, mixed> */
    private function human(array $data = []): array
    {
        return $data + [SpamGuard::TOKEN => Crypt::encryptString((string) now()->subSeconds(20)->getTimestamp()), 'consent' => '1'];
    }

    public function test_a_contact_message_is_saved_and_notified(): void
    {
        $this->get('/contact')->assertOk()->assertSee('data-ajax-form', false)->assertSee(SpamGuard::HONEYPOT);

        $this->from('/contact')->post('/contact', $this->human([
            'name' => 'Awa Traoré', 'organization' => 'Lycée de Kati', 'phone' => '70 11 22 33', 'subject' => 'Maintenance', 'message' => 'Nos imprimantes sont en panne.',
        ]))->assertRedirect('/contact')->assertSessionHas('demande_sent');

        $demande = Demande::firstOrFail();
        $this->assertSame(DemandeType::Contact, $demande->type);
        $this->assertSame(DemandeStatus::Nouvelle, $demande->status);
        $this->assertNotNull($demande->consent_at);
        $this->assertNotNull($demande->notified_at);
        Mail::assertSent(NouvelleDemande::class, fn ($mail) => $mail->hasTo('equipe@csb.test') && $mail->demande->is($demande));
        Mail::assertNotSent(DemandeAccuse::class); // pas d'e-mail laissé : pas d'accusé de réception
        $this->assertTrue(ActivityLog::where('action', 'demandes.received')->exists());

        $this->get('/contact')->assertSee('Message envoyé');
    }

    public function test_the_visitor_who_left_an_email_receives_an_acknowledgment(): void
    {
        $this->postJson('/contact', $this->human([
            'name' => 'Awa Traoré', 'email' => 'awa@exemple.ml', 'subject' => 'Maintenance du parc', 'message' => 'Nos imprimantes sont en panne.',
        ]))->assertOk();

        $demande = Demande::firstOrFail();
        Mail::assertSent(DemandeAccuse::class, function (DemandeAccuse $mail) use ($demande) {
            $html = $mail->render();

            return $mail->hasTo('awa@exemple.ml') && $mail->hasReplyTo('equipe@csb.test') && $mail->demande->is($demande)
                && str_contains($mail->envelope()->subject, $demande->reference())
                && str_contains($html, 'Bonjour Awa Traoré') && str_contains($html, 'votre message') && str_contains($html, 'Maintenance du parc');
        });
        Mail::assertSent(NouvelleDemande::class, 1);
    }

    public function test_the_ajax_submission_returns_json_and_validation_errors(): void
    {
        $this->postJson('/contact', $this->human(['name' => 'Moussa', 'email' => 'moussa@exemple.ml', 'subject' => 'Info', 'message' => 'Bonjour, je souhaite un renseignement.']))
            ->assertOk()->assertJsonStructure(['title', 'message']);

        $this->postJson('/contact', [SpamGuard::TOKEN => Crypt::encryptString((string) now()->subMinute()->getTimestamp()), 'name' => '', 'message' => 'court'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'phone', 'subject', 'message', 'consent']);

        $this->assertSame(1, Demande::count());
    }

    public function test_robots_get_a_success_but_nothing_is_saved(): void
    {
        $valid = ['name' => 'Bot', 'email' => 'bot@spam.test', 'subject' => 'Promo', 'message' => 'Achetez nos produits miracles.'];

        $this->postJson('/contact', $this->human($valid + [SpamGuard::HONEYPOT => 'http://spam.test']))->assertOk();
        $this->postJson('/contact', $valid + ['consent' => '1', SpamGuard::TOKEN => SpamGuard::token()])->assertOk(); // envoyé aussitôt affiché
        $this->postJson('/contact', $valid + ['consent' => '1', SpamGuard::TOKEN => 'falsifié'])->assertOk();

        $this->assertSame(0, Demande::count());
        Mail::assertNothingSent();
    }

    public function test_submissions_are_rate_limited(): void
    {
        $data = $this->human(['name' => 'Pressé', 'email' => 'a@b.ml', 'subject' => 'X', 'message' => 'Message de test numéro un.']);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/contact', $data)->assertOk();
        }
        $this->postJson('/contact', $data)->assertStatus(429);
    }

    public function test_a_quote_request_keeps_its_attachment_private(): void
    {
        $logiciel = Logiciel::factory()->create(['name' => 'KalanNet', 'slug' => 'kalannet']);

        $this->get('/devis?logiciel=kalannet')->assertOk()->assertSee('Devis pour le logiciel')->assertSee('KalanNet');
        $this->postJson('/devis', $this->human([
            'name' => 'Directeur', 'email' => 'dir@ecole.ml', 'need_type' => 'logiciel', 'message' => 'Gestion de 800 élèves, 3 sites.',
            'budget' => '2 000 000 FCFA', 'logiciel' => 'kalannet', 'attachment' => UploadedFile::fake()->create('cahier.pdf', 200, 'application/pdf'),
        ]))->assertOk();

        $demande = Demande::firstOrFail();
        $this->assertSame('KalanNet', $demande->subject_label);
        $this->assertTrue($demande->subjectModel->is($logiciel));
        $this->assertSame('cahier.pdf', $demande->attachment_name);
        Storage::disk('local')->assertExists($demande->attachment_path);
        $this->assertStringStartsWith('demandes/', $demande->attachment_path);

        $this->postJson('/devis', $this->human(['name' => 'X', 'email' => 'x@y.ml', 'need_type' => 'autre', 'message' => 'Description suffisante.', 'attachment' => UploadedFile::fake()->create('virus.exe', 10)]))
            ->assertUnprocessable()->assertJsonValidationErrors('attachment');
        $this->postJson('/devis', $this->human(['name' => 'X', 'email' => 'x@y.ml', 'need_type' => 'autre', 'message' => 'Description suffisante.', 'attachment' => UploadedFile::fake()->create('gros.pdf', 6000, 'application/pdf')]))
            ->assertUnprocessable()->assertJsonValidationErrors('attachment');
        $this->assertSame(1, Demande::count());
    }

    public function test_demonstration_and_availability_forms_are_prefilled(): void
    {
        $logiciel = Logiciel::factory()->create(['name' => 'JAGO DANAYA', 'slug' => 'jago-danaya']);
        $archive = Logiciel::factory()->create(['slug' => 'vieux', 'status' => LogicielStatus::Archive]);
        $materiel = Materiel::factory()->create(['designation' => 'Imprimante Canon', 'slug' => 'imprimante-canon', 'price' => null]);

        $this->get('/demonstration/jago-danaya')->assertOk()->assertSee('JAGO DANAYA');
        $this->get('/demonstration/vieux')->assertNotFound();
        $this->get('/logiciels/jago-danaya')->assertSee('/demonstration/jago-danaya');
        $this->postJson('/demonstration/jago-danaya', $this->human(['name' => 'Gérant', 'phone' => '76 00 00 00', 'message' => 'Boutique de 2 caisses.', 'availability' => 'Mardi matin']))->assertOk();

        $this->get('/disponibilite/imprimante-canon')->assertOk()->assertSee('Demander le prix');
        $this->get('/materiel/imprimante-canon')->assertSee('/disponibilite/imprimante-canon');
        $this->postJson('/disponibilite/imprimante-canon', $this->human(['name' => 'Client', 'phone' => '+223 76 00 00 01', 'message' => '3 unités']))->assertOk();

        $this->assertSame([DemandeType::Demonstration, DemandeType::Disponibilite], Demande::orderBy('id')->get()->pluck('type')->all());
        $this->assertSame('Mardi matin', Demande::first()->availability);
        $this->assertSame($materiel->id, Demande::orderByDesc('id')->first()->subject_id);
        $this->assertNotNull($archive);
    }

    public function test_a_mail_failure_never_loses_the_request(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP injoignable'));

        $this->postJson('/contact', $this->human(['name' => 'Fatou', 'email' => 'f@csb.ml', 'subject' => 'Test', 'message' => 'Message important à conserver.']))->assertOk();

        $demande = Demande::firstOrFail();
        $this->assertNull($demande->notified_at);
        $this->assertStringContainsString('SMTP injoignable', $demande->notification_error);
        $this->assertTrue(ActivityLog::where('action', 'demandes.notification_failed')->exists());
        $this->assertTrue(ActivityLog::where('action', 'demandes.acknowledgment_failed')->exists());
    }
}
