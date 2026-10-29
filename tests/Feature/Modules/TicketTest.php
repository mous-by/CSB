<?php

namespace Tests\Feature\Modules;

use App\Enums\TicketStatus;
use App\Mail\TicketForClient;
use App\Mail\TicketForTeam;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Tickets d'assistance (cahier §15.3) : ouverture par le client, numérotation, fil sans notes internes, réponses,
 * réouverture, notifications, traitement par l'équipe, cloisonnement entre clients, permissions.
 */
class TicketTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private function openTicket(Client $client, array $data = []): Ticket
    {
        $this->actingAs($client, 'client')->post('/espace-client/tickets', $data + [
            'subject' => 'Imprimante qui ne répond plus', 'category' => 'reparation', 'priority' => 'haute',
            'device' => 'Canon LBP', 'message' => 'Depuis ce matin, l\'imprimante affiche une erreur E000.',
        ])->assertRedirect();

        return Ticket::latest('id')->firstOrFail();
    }

    public function test_a_client_opens_a_numbered_ticket_and_the_team_is_notified(): void
    {
        Mail::fake();
        Storage::fake('local');
        $client = Client::factory()->create();

        $this->actingAs($client, 'client')->get('/espace-client/tickets')->assertOk()->assertSee('Aucune demande');
        $this->get('/espace-client/tickets/nouveau')->assertOk();

        $ticket = $this->openTicket($client, ['attachment' => UploadedFile::fake()->image('erreur.png')]);
        $second = $this->openTicket($client, ['subject' => 'Wi-Fi lent']);

        $year = now()->year;
        $this->assertSame("TCK-{$year}-0001", $ticket->number);
        $this->assertSame("TCK-{$year}-0002", $second->number);
        $this->assertSame(TicketStatus::Ouvert, $ticket->status);
        $this->assertTrue($ticket->awaiting_team);
        $this->assertNotNull($ticket->messages()->first()->attachment_path);
        Storage::disk('local')->assertExists($ticket->messages()->first()->attachment_path);
        Mail::assertSent(TicketForTeam::class, 2);
        // Rendu réel des e-mails (la variable « $message » des vues d'e-mail est réservée par Laravel).
        Mail::assertSent(TicketForTeam::class, fn (TicketForTeam $mail) => str_contains($mail->render(), 'erreur E000'));
        // Accusé de réception au client, avec son message en rappel.
        Mail::assertSent(TicketForClient::class, fn (TicketForClient $mail) => $mail->opened && $mail->hasTo($client->email) && $mail->reply?->body === 'Depuis ce matin, l\'imprimante affiche une erreur E000.');
        $this->assertStringContainsString('bien reçue', (new TicketForClient($ticket, $ticket->messages()->first(), true))->envelope()->subject);
        $this->assertTrue(ActivityLog::where('action', 'tickets.opened')->exists());

        $this->get("/espace-client/tickets/{$ticket->id}")->assertOk()->assertSee('erreur E000')->assertSee('erreur.png');
        $this->get('/espace-client')->assertOk()->assertSee('Tickets');
    }

    public function test_validation_of_the_ticket_form(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($client, 'client')->post('/espace-client/tickets', ['subject' => '', 'category' => 'inconnue', 'priority' => 'normale', 'message' => 'court'])
            ->assertSessionHasErrors(['subject', 'category', 'message']);
        $this->assertSame(0, Ticket::count());
    }

    public function test_a_client_never_sees_another_clients_ticket_nor_internal_notes(): void
    {
        Mail::fake();
        $this->syncPermissions();
        $owner = Client::factory()->create();
        $other = Client::factory()->create();
        $ticket = $this->openTicket($owner);

        $this->actingAs($other, 'client')->get("/espace-client/tickets/{$ticket->id}")->assertNotFound();
        $this->post("/espace-client/tickets/{$ticket->id}/messages", ['body' => 'Intrusion'])->assertNotFound();
        $this->get('/espace-client/tickets')->assertDontSee($ticket->number);

        $agent = User::factory()->commercial()->create();
        $this->actingAs($agent)->post("/admin/tickets/{$ticket->id}/messages", ['body' => 'Client difficile, rappeler demain.', 'internal' => '1'])->assertRedirect();
        // Seul l'accusé de réception de l'ouverture est parti : la note interne n'envoie rien au client.
        Mail::assertNotSent(TicketForClient::class, fn (TicketForClient $mail) => ! $mail->opened);

        $note = $ticket->messages()->where('is_internal', true)->sole();
        $this->actingAs($owner, 'client')->get("/espace-client/tickets/{$ticket->id}")->assertOk()->assertDontSee('Client difficile');
        $this->get("/espace-client/tickets/{$ticket->id}/messages/{$note->id}/piece-jointe")->assertNotFound();
    }

    public function test_the_team_replies_changes_status_and_the_client_reopens_by_answering(): void
    {
        Mail::fake();
        $this->syncPermissions();
        $client = Client::factory()->create();
        $ticket = $this->openTicket($client);
        $agent = User::factory()->commercial()->create();

        $this->actingAs($agent)->get('/admin/tickets')->assertOk()->assertSee($ticket->number);
        $this->get("/admin/tickets/{$ticket->id}")->assertOk()->assertSee('erreur E000');

        $this->post("/admin/tickets/{$ticket->id}/messages", ['body' => 'Pouvez-vous redémarrer l\'imprimante ?', 'status' => 'attente_client'])->assertRedirect();
        $ticket->refresh();
        $this->assertSame(TicketStatus::AttenteClient, $ticket->status);
        $this->assertFalse($ticket->awaiting_team);
        $this->assertSame($agent->id, $ticket->assigned_to, 'La première personne qui répond prend le ticket.');
        Mail::assertSent(TicketForClient::class, fn (TicketForClient $mail) => ! $mail->opened && $mail->hasTo($client->email)
            && str_contains($mail->render(), 'redémarrer l&#039;imprimante') && str_contains($mail->render(), 'vous a répondu'));

        $this->put("/admin/tickets/{$ticket->id}/statut", ['status' => 'resolu'])->assertRedirect();
        $this->assertSame(TicketStatus::Resolu, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);
        $this->assertTrue(ActivityLog::where('action', 'tickets.status_changed')->where('subject_id', $ticket->id)->exists());

        $this->put("/admin/tickets/{$ticket->id}", ['priority' => 'urgente', 'assigned_to' => null])->assertRedirect();
        $this->assertSame('urgente', $ticket->fresh()->priority->value);
        $this->assertNull($ticket->fresh()->assigned_to);

        // Le problème revient : la réponse du client rouvre le ticket.
        $this->actingAs($client, 'client')->post("/espace-client/tickets/{$ticket->id}/messages", ['body' => 'Le problème est revenu.'])->assertRedirect();
        $ticket->refresh();
        $this->assertSame(TicketStatus::EnCours, $ticket->status);
        $this->assertTrue($ticket->awaiting_team);
        $this->assertNull($ticket->closed_at);
    }

    public function test_permissions_of_the_ticket_screens(): void
    {
        Mail::fake();
        $this->syncPermissions();
        $ticket = $this->openTicket(Client::factory()->create());

        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin/tickets')->assertForbidden();
        $commercial = User::factory()->commercial()->create();
        $this->actingAs($commercial)->delete("/admin/tickets/{$ticket->id}")->assertForbidden();
        $this->get('/admin')->assertOk()->assertSee('Tickets');

        $this->actingAs(User::factory()->administrateur()->create())->delete("/admin/tickets/{$ticket->id}")->assertRedirect('/admin/tickets');
        $this->assertSoftDeleted($ticket);
    }

    public function test_a_failed_notification_never_blocks_the_ticket(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP indisponible'));
        $client = Client::factory()->create();

        $ticket = $this->openTicket($client);

        $this->assertTrue(ActivityLog::where('action', 'tickets.notification_failed')->where('subject_id', $ticket->id)->exists());
    }
}
