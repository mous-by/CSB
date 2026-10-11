<?php

namespace Tests\Feature\Modules;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Support\SiteSettings;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Alertes WhatsApp (cahier §15.3) : modèle approuvé envoyé à chaque numéro pour les événements choisis, rien sans
 * configuration, échec journalisé sans bloquer la demande, écran de réglage et message de test.
 */
class WhatsAppTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private function configure(string $recipients = "74 74 56 69\n+22367205736", string $events = "demandes\ncommandes\ntickets"): void
    {
        config(['services.whatsapp.token' => 'jeton-test', 'services.whatsapp.phone_number_id' => '123456', 'services.whatsapp.template' => 'csb_alerte']);
        app(SiteSettings::class)->save(['whatsapp.recipients' => $recipients, 'whatsapp.events' => $events]);
    }

    private function sendContact(): void
    {
        RateLimiter::clear('demandes-min|127.0.0.1');
        $this->post('/contact', ['name' => 'Awa Traoré', 'phone' => '70 11 22 33', 'email' => 'awa@exemple.ml', 'subject' => 'Renseignement', 'message' => 'Bonjour, je voudrais un renseignement sur KalanNet.', 'consent' => '1',
            SpamGuard::TOKEN => Crypt::encryptString((string) now()->subSeconds(20)->getTimestamp())])->assertSessionHasNoErrors();
    }

    public function test_a_new_request_alerts_every_recipient_with_the_approved_template(): void
    {
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        $this->configure();

        $this->sendContact();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://graph.facebook.com/v21.0/123456/messages'
            && $r->hasHeader('Authorization', 'Bearer jeton-test')
            && $r['to'] === '22374745669' && $r['type'] === 'template' && $r['template']['name'] === 'csb_alerte'
            && str_contains($r['template']['components'][0]['parameters'][0]['text'], 'Awa Traoré'));
    }

    public function test_nothing_is_sent_without_configuration_or_for_a_disabled_event(): void
    {
        Mail::fake();
        Http::fake();

        $this->sendContact();
        Http::assertNothingSent();

        $this->configure(events: 'commandes');
        $this->sendContact();
        Http::assertNothingSent();
    }

    public function test_a_failure_is_logged_and_never_blocks_the_request(): void
    {
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not approved']], 400)]);
        $this->configure('74 74 56 69');

        $this->sendContact();

        $this->assertDatabaseHas('demandes', ['name' => 'Awa Traoré']);
        $this->assertTrue(ActivityLog::where('action', 'whatsapp.failed')->exists());
    }

    public function test_a_new_ticket_also_alerts_the_team(): void
    {
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response([])]);
        $this->configure('74 74 56 69', 'tickets');

        $this->actingAs(Client::factory()->create(), 'client')->post('/espace-client/tickets', ['subject' => 'Écran noir', 'category' => 'reparation', 'priority' => 'urgente', 'message' => 'L\'écran reste noir au démarrage.']);

        Http::assertSent(fn (Request $r) => str_contains($r['template']['components'][0]['parameters'][0]['text'], 'Écran noir'));
    }

    public function test_the_settings_screen_saves_numbers_and_sends_a_test_message(): void
    {
        $this->syncPermissions();
        Http::fake(['graph.facebook.com/*' => Http::response([])]);
        $admin = User::factory()->administrateur()->create();

        $this->actingAs($admin)->get('/admin/parametres/whatsapp')->assertOk()->assertSee('API non configurée');
        $this->put('/admin/parametres/whatsapp', ['recipients' => "74 74 56 69\n12"])->assertSessionHasErrors('recipients');
        $this->put('/admin/parametres/whatsapp', ['recipients' => '74 74 56 69', 'events' => ['demandes', 'tickets']])->assertRedirect('/admin/parametres/whatsapp');
        $this->assertSame("demandes\ntickets", app(SiteSettings::class)->get('whatsapp.events'));

        $this->post('/admin/parametres/whatsapp/test')->assertSessionHas('error');
        config(['services.whatsapp.token' => 'jeton-test', 'services.whatsapp.phone_number_id' => '123456']);
        $this->post('/admin/parametres/whatsapp/test')->assertSessionHas('status');
        Http::assertSentCount(1);

        $this->actingAs(User::factory()->commercial()->create())->get('/admin/parametres/whatsapp')->assertForbidden();
    }
}
