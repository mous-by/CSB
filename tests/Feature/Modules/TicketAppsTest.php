<?php

namespace Tests\Feature\Modules;

use App\Enums\TicketStatus;
use App\Jobs\SendPushNotification;
use App\Mail\TicketForClient;
use App\Mail\TicketForTeam;
use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\PushSubscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Tickets ouverts depuis les applications de CSB (KalanNet…) par l'API : jeton de portée « tickets », cloisonnement par
 * application et par utilisateur, fil sans notes internes, réponse qui rouvre, notifications à l'équipe (e-mail, push).
 */
class TicketAppsTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private function payload(array $reporter = []): array
    {
        return [
            'subject' => 'Les bulletins ne s\'impriment plus', 'category' => 'logiciel', 'priority' => 'haute',
            'message' => 'Depuis ce matin, l\'impression des bulletins affiche une page blanche.',
            'reporter' => $reporter + ['ref' => '42', 'name' => 'Awa Traoré', 'role' => 'Directrice', 'organization' => 'École Kalan', 'email' => 'awa@example.com', 'phone' => '+22376000000'],
        ];
    }

    public function test_an_administrator_connects_an_application_and_its_token_only_opens_tickets(): void
    {
        $this->syncPermissions();
        $admin = User::factory()->administrateur()->create();

        $this->actingAs($admin)->get('/admin/tickets')->assertOk()->assertSee('Applications connectées');
        $this->get('/admin/tickets/applications')->assertOk()->assertSee('BRANCHER UNE APPLICATION');
        $this->post('/admin/tickets/applications', ['name' => 'KalanNet'])->assertRedirect('/admin/tickets/applications')->assertSessionHas('new_token');
        $this->post('/admin/tickets/applications', ['name' => 'KalanNet'])->assertSessionHasErrors('name');

        $token = ApiToken::where('name', 'KalanNet')->firstOrFail();
        $this->assertSame(ApiToken::SCOPE_TICKETS, $token->scope);
        $this->assertTrue(ActivityLog::where('module', 'tickets')->where('action', 'like', '%created%')->exists());

        $this->get('/admin/synchro')->assertOk()->assertDontSee('KalanNet');
        $this->delete("/admin/synchro/jetons/{$token->id}")->assertNotFound();

        $commercial = User::factory()->commercial()->create();
        $this->actingAs($commercial)->get('/admin/tickets/applications')->assertForbidden();
    }

    public function test_tokens_are_scoped_stock_and_tickets_never_mix(): void
    {
        [, $stock] = ApiToken::issue('Logiciel de stock', null);
        [, $app] = ApiToken::issue('KalanNet', null, ApiToken::SCOPE_TICKETS);

        $this->withToken($stock)->getJson('/api/v1/tickets?reporter_ref=42')->assertUnauthorized();
        $this->withToken($app)->getJson('/api/v1/stock')->assertUnauthorized();
        $this->withToken($app)->getJson('/api/v1/tickets/options')->assertOk()->assertJsonPath('categories.logiciel', 'Assistance sur un logiciel');
    }

    public function test_an_application_user_opens_and_follows_a_ticket_and_the_team_is_notified(): void
    {
        Mail::fake();
        Bus::fake([SendPushNotification::class]);
        $this->syncPermissions();
        config(['services.webpush.public_key' => 'BEfoyTllFQj3MqJzpO329mnbqUjvNPg4LBa4cxtH8z49GPqryoi6sRiQYus5tQiIpngnKqcaR0WCvQaI4BY1nJU', 'services.webpush.private_key' => 'eCcMwKrvTzvEdM3OJ62ihNOtaev6Zmpl9v7R0TlJJhA']);
        $agent = User::factory()->commercial()->create();
        $gestionnaire = User::factory()->gestionnaire()->create();
        foreach ([$agent, $gestionnaire] as $i => $user) {
            PushSubscription::create(['user_id' => $user->id, 'endpoint' => "https://push.example.com/{$i}", 'endpoint_hash' => PushSubscription::hashEndpoint("https://push.example.com/{$i}"), 'public_key' => 'x', 'auth_token' => 'y']);
        }
        [, $app] = ApiToken::issue('KalanNet', null, ApiToken::SCOPE_TICKETS);
        [, $other] = ApiToken::issue('DojoManager', null, ApiToken::SCOPE_TICKETS);

        $this->withToken($app)->postJson('/api/v1/tickets', ['subject' => 'x', 'category' => 'logiciel', 'message' => 'court'])
            ->assertUnprocessable()->assertJsonValidationErrors(['message', 'reporter.ref', 'reporter.name']);

        $number = $this->withToken($app)->postJson('/api/v1/tickets', $this->payload())->assertCreated()
            ->assertJsonPath('data.status', 'ouvert')->assertJsonPath('data.messages.0.from', 'reporter')->json('data.number');

        $ticket = Ticket::where('number', $number)->firstOrFail();
        $this->assertNull($ticket->client_id);
        $this->assertSame('KalanNet', $ticket->source_app);
        $this->assertSame('Awa Traoré', $ticket->requesterName());
        Mail::assertSent(TicketForTeam::class);
        // Push : seulement les membres qui voient les tickets (le gestionnaire n'a pas tickets.view par défaut).
        Bus::assertDispatchedTimes(SendPushNotification::class, 1);
        $this->assertTrue(ActivityLog::where('action', 'tickets.opened')->where('channel', 'api')->exists());

        // L'équipe voit la source et le demandeur, répond, et ajoute une note interne.
        $this->actingAs($agent)->get('/admin/tickets')->assertOk()->assertSee('Awa Traoré')->assertSee('KalanNet');
        $this->get("/admin/tickets/{$ticket->id}")->assertOk()->assertSee('Via KalanNet')->assertSee('École Kalan');
        $this->post("/admin/tickets/{$ticket->id}/messages", ['body' => 'Mettez à jour le navigateur puis réessayez.', 'status' => 'attente_client'])->assertRedirect();
        $this->post("/admin/tickets/{$ticket->id}/messages", ['body' => 'Note : problème connu sur Chrome 140.', 'internal' => '1'])->assertRedirect();
        Mail::assertSent(TicketForClient::class, fn ($mail) => $mail->hasTo('awa@example.com'));

        $thread = $this->withToken($app)->getJson("/api/v1/tickets/{$number}?reporter_ref=42")->assertOk()
            ->assertJsonPath('data.status', 'attente_client')->assertJsonPath('data.awaiting_reporter', true)->json('data.messages');
        $this->assertCount(2, $thread);
        $this->assertSame('csb', $thread[1]['from']);
        $this->assertStringNotContainsString('Chrome 140', json_encode($thread));

        // Cloisonnement : autre utilisateur, autre application.
        $this->withToken($app)->getJson("/api/v1/tickets/{$number}?reporter_ref=43")->assertNotFound();
        $this->withToken($other)->getJson("/api/v1/tickets/{$number}?reporter_ref=42")->assertNotFound();
        $this->withToken($app)->getJson('/api/v1/tickets?reporter_ref=43')->assertOk()->assertJsonCount(0, 'data');
        $this->withToken($app)->getJson('/api/v1/tickets?reporter_ref=42')->assertOk()->assertJsonCount(1, 'data');

        // La réponse du demandeur rend la main à l'équipe.
        $this->withToken($app)->postJson("/api/v1/tickets/{$number}/messages", ['reporter_ref' => '42', 'body' => 'Toujours une page blanche.'])->assertCreated();
        $ticket->refresh();
        $this->assertSame(TicketStatus::EnCours, $ticket->status);
        $this->assertTrue($ticket->awaiting_team);
        Bus::assertDispatchedTimes(SendPushNotification::class, 2);
    }
}
