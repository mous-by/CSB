<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleName;
use App\Jobs\SendPushNotification;
use App\Models\ApiToken;
use App\Models\PushSubscription;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Dashboard\RealDashboardData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Rôle « Administrateur KalanNet » (demande de CSB, 2026-11-10) : il ne voit et ne traite que les tickets venus de
 * l'application KalanNet — liste, fiche, pièces jointes, tableau de bord, pastille, cloche et notifications push.
 */
class AdminKalanNetTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private function ticketFrom(string $plainToken, string $subject): Ticket
    {
        $number = $this->withToken($plainToken)->postJson('/api/v1/tickets', [
            'subject' => $subject, 'category' => 'logiciel', 'priority' => 'haute',
            'message' => 'Depuis ce matin, l\'impression des bulletins affiche une page blanche.',
            'reporter' => ['ref' => '42', 'name' => 'Awa Traoré', 'role' => 'Directrice', 'organization' => 'École Kalan', 'email' => 'awa@example.com'],
        ])->assertCreated()->json('data.number');
        $this->flushHeaders();

        return Ticket::where('number', $number)->firstOrFail();
    }

    public function test_he_sees_and_handles_only_kalannet_tickets(): void
    {
        Mail::fake();
        $this->syncPermissions();
        [, $kalannet] = ApiToken::issue('KalanNet', null, ApiToken::SCOPE_TICKETS);
        [, $dojo] = ApiToken::issue('DojoManager', null, ApiToken::SCOPE_TICKETS);
        $sien = $this->ticketFrom($kalannet, 'Bulletins KalanNet en panne');
        $autre = $this->ticketFrom($dojo, 'Ceintures DojoManager perdues');

        $admin = User::factory()->adminKalanNet()->create();
        $this->actingAs($admin);

        // Tableau de bord : seulement ses tickets, aucun module de CSB.
        $this->get('/admin')->assertOk()->assertSee('Tickets de KalanNet')->assertSee('Bulletins KalanNet en panne')
            ->assertDontSee('Ceintures DojoManager perdues')->assertSee('Mes tickets')
            ->assertDontSee('Demandes commerciales')->assertDontSee('Contenus du site')->assertDontSee('Aucun module ne vous est encore attribué');

        // Liste et fiche : l'autre application est invisible, même par son adresse.
        $this->get('/admin/tickets')->assertOk()->assertSee($sien->number)->assertDontSee($autre->number)->assertDontSee('Applications connectées')->assertDontSee('>Commercial<', false);
        $this->get("/admin/tickets/{$sien->id}")->assertOk()->assertSee('Bulletins KalanNet en panne');
        $this->get("/admin/tickets/{$autre->id}")->assertForbidden();
        $this->post("/admin/tickets/{$autre->id}/messages", ['body' => 'Je regarde'])->assertForbidden();

        // Il traite : réponse, note interne, statut, priorité ; pas de suppression.
        $this->post("/admin/tickets/{$sien->id}/messages", ['body' => 'Corrigé, réessayez.', 'status' => 'resolu'])->assertRedirect();
        $this->post("/admin/tickets/{$sien->id}/messages", ['body' => 'Cause : cache.', 'internal' => 1])->assertRedirect();
        $this->put("/admin/tickets/{$sien->id}", ['priority' => 'basse', 'assigned_to' => $admin->id])->assertRedirect();
        $this->assertSame(3, $sien->messages()->count());
        $this->assertSame($admin->id, $sien->fresh()->assigned_to);
        $this->delete("/admin/tickets/{$sien->id}")->assertForbidden();

        // Rien d'autre de l'administration.
        foreach (['/admin/tickets/applications', '/admin/demandes', '/admin/clients', '/admin/users', '/admin/aide', '/admin/logiciels'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_badge_bell_and_push_only_for_his_application(): void
    {
        Mail::fake();
        Bus::fake([SendPushNotification::class]);
        $this->syncPermissions();
        config(['services.webpush.public_key' => 'BEfoyTllFQj3MqJzpO329mnbqUjvNPg4LBa4cxtH8z49GPqryoi6sRiQYus5tQiIpngnKqcaR0WCvQaI4BY1nJU', 'services.webpush.private_key' => 'eCcMwKrvTzvEdM3OJ62ihNOtaev6Zmpl9v7R0TlJJhA']);
        $admin = User::factory()->adminKalanNet()->create();
        PushSubscription::create(['user_id' => $admin->id, 'endpoint' => 'https://push.example.com/k', 'endpoint_hash' => PushSubscription::hashEndpoint('https://push.example.com/k'), 'public_key' => 'x', 'auth_token' => 'y']);
        [, $kalannet] = ApiToken::issue('KalanNet', null, ApiToken::SCOPE_TICKETS);
        [, $dojo] = ApiToken::issue('DojoManager', null, ApiToken::SCOPE_TICKETS);

        $this->ticketFrom($dojo, 'Ceintures DojoManager perdues');
        Bus::assertNotDispatched(SendPushNotification::class);
        $this->ticketFrom($kalannet, 'Bulletins KalanNet en panne');
        Bus::assertDispatchedTimes(SendPushNotification::class, 1);

        $dashboard = app(RealDashboardData::class);
        $this->assertSame(['tickets' => 1], $dashboard->sidebarBadges($admin));
        $this->assertStringContainsString('1 ticket(s)', $dashboard->notifications($admin)[0]['text']);

        // L'équipe de CSB voit toujours les deux.
        $this->assertSame(2, $dashboard->sidebarBadges(User::factory()->administrateur()->create())['tickets']);
    }

    public function test_the_csb_administrator_creates_the_account_and_can_assign_him_kalannet_tickets_only(): void
    {
        Mail::fake();
        $this->syncPermissions();
        $this->assertTrue(RoleName::Administrateur->canManage(RoleName::AdminKalanNet));
        $this->assertFalse(RoleName::AdminKalanNet->canManage(RoleName::Commercial));
        $this->assertSame('Administrateur KalanNet', RoleName::AdminKalanNet->label());

        [, $kalannet] = ApiToken::issue('KalanNet', null, ApiToken::SCOPE_TICKETS);
        [, $dojo] = ApiToken::issue('DojoManager', null, ApiToken::SCOPE_TICKETS);
        $sien = $this->ticketFrom($kalannet, 'Bulletins KalanNet en panne');
        $autre = $this->ticketFrom($dojo, 'Ceintures DojoManager perdues');
        $admin = User::factory()->adminKalanNet()->create(['name' => 'Moussa KalanNet']);

        $this->actingAs(User::factory()->administrateur()->create());
        $this->get("/admin/tickets/{$sien->id}")->assertOk()->assertSee('Moussa KalanNet');
        $this->get("/admin/tickets/{$autre->id}")->assertOk()->assertDontSee('Moussa KalanNet');
        $this->put("/admin/tickets/{$autre->id}", ['priority' => 'haute', 'assigned_to' => $admin->id])->assertSessionHasErrors('assigned_to');
        $this->assertNull($autre->fresh()->assigned_to);
    }
}
