<?php

namespace Tests\Feature\Modules;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Demande;
use App\Models\User;
use App\Notifications\ClientResetPassword;
use App\Notifications\ClientVerifyEmail;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Espace client (cahier §15.3) : inscription, connexion, confirmation de l'adresse (qui rattache les demandes
 * antérieures), demandes faites connecté, documents privés, désactivation ; gestion par l'équipe.
 */
class ClientAreaTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    public function test_a_visitor_registers_confirms_his_email_and_finds_his_past_requests(): void
    {
        Notification::fake();
        $old = Demande::factory()->create(['email' => 'awa@exemple.ml', 'client_id' => null]);
        Demande::factory()->create(['email' => 'autre@exemple.ml']);

        $this->get('/espace-client')->assertRedirect('/espace-client/connexion');
        $this->post('/espace-client/inscription', ['name' => 'Awa Traoré', 'email' => 'Awa@Exemple.ML', 'phone' => '70 11 22 33', 'password' => 'Secret-123', 'password_confirmation' => 'Secret-123', 'consent' => '1'])
            ->assertRedirect('/espace-client');

        $client = Client::sole();
        $this->assertSame('awa@exemple.ml', $client->email);
        $this->assertSame('+22370112233', $client->phone);
        $this->assertNull($old->fresh()->client_id, 'Pas de rattachement avant la confirmation de l\'adresse.');
        Notification::assertSentTo($client, ClientVerifyEmail::class);

        $this->get('/espace-client')->assertOk()->assertSee('Confirmez votre adresse')->assertSee('Aucune demande');

        $url = URL::temporarySignedRoute('client.verification.verify', now()->addDay(), ['client' => $client->id, 'hash' => sha1($client->email)]);
        $this->get($url)->assertRedirect('/espace-client');
        $this->assertSame($client->id, $old->fresh()->client_id);
        $this->assertSame(1, $client->demandes()->count());
        $this->get('/espace-client/demandes')->assertOk()->assertSee($old->reference());

        $this->get(URL::temporarySignedRoute('client.verification.verify', now()->addDay(), ['client' => $client->id, 'hash' => sha1('pirate@x.ml')]))->assertForbidden();
        $this->get('/espace-client/verification/'.$client->id.'/'.sha1($client->email))->assertForbidden(); // non signé
    }

    public function test_a_client_may_choose_a_simple_password(): void
    {
        Notification::fake();

        $this->post('/espace-client/inscription', ['name' => 'Fanta Keïta', 'email' => 'fanta@exemple.ml', 'phone' => '70 22 33 44', 'password' => '123', 'password_confirmation' => '123', 'consent' => '1'])
            ->assertSessionHasErrors('password');
        $this->post('/espace-client/inscription', ['name' => 'Fanta Keïta', 'email' => 'fanta@exemple.ml', 'phone' => '70 22 33 44', 'password' => '1234', 'password_confirmation' => '1234', 'consent' => '1'])
            ->assertRedirect('/espace-client');

        auth('client')->logout();
        $this->post('/espace-client/connexion', ['login' => 'fanta@exemple.ml', 'password' => '1234'])->assertRedirect('/espace-client');
        auth('client')->logout();
        $this->get('/espace-client/inscription')->assertOk()->assertSee('pw-toggle', false);
    }

    public function test_login_by_email_or_phone_with_attempt_limit(): void
    {
        Client::factory()->create(['email' => 'moussa@exemple.ml', 'phone' => '+22376000001', 'password' => 'Secret-123']);

        $this->post('/espace-client/connexion', ['login' => '76 00 00 01', 'password' => 'Secret-123'])->assertRedirect('/espace-client');
        $this->post('/espace-client/deconnexion')->assertRedirect('/');
        $this->post('/espace-client/connexion', ['login' => 'MOUSSA@exemple.ml', 'password' => 'Secret-123'])->assertRedirect('/espace-client');
        $this->post('/espace-client/deconnexion');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/espace-client/connexion', ['login' => 'moussa@exemple.ml', 'password' => 'faux'])->assertSessionHasErrors('login');
        }
        $this->post('/espace-client/connexion', ['login' => 'moussa@exemple.ml', 'password' => 'Secret-123'])->assertStatus(302)->assertSessionHasErrors('login');
        $this->assertGuest('client');
    }

    public function test_a_request_sent_while_logged_in_goes_to_the_client_area(): void
    {
        Mail::fake();
        $client = Client::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($client, 'client')->get('/contact')->assertSee('value="'.$client->email.'"', false);
        $this->postJson('/contact', ['name' => $client->name, 'email' => $client->email, 'subject' => 'Panne', 'message' => 'Mon imprimante ne répond plus.', 'consent' => '1', SpamGuard::TOKEN => Crypt::encryptString((string) now()->subMinute()->timestamp)])->assertOk();

        $this->assertSame($client->id, Demande::sole()->client_id);
        $this->get('/espace-client')->assertSee(Demande::sole()->reference());
    }

    public function test_documents_are_private_to_their_client(): void
    {
        Storage::fake('local');
        $this->syncPermissions();
        $owner = Client::factory()->create();
        $other = Client::factory()->create();

        $this->actingAs(User::factory()->commercial()->create())
            ->post("/admin/clients/{$owner->id}/documents", ['title' => 'Devis réseau', 'type' => 'devis', 'file' => UploadedFile::fake()->create('devis.pdf', 30, 'application/pdf')])
            ->assertSessionHas('status');
        $document = ClientDocument::sole();
        $this->assertTrue(ActivityLog::where('action', 'clients.document_added')->exists());

        $this->actingAs($owner, 'client')->get('/espace-client/documents')->assertSee('Devis réseau');
        $this->get("/espace-client/documents/{$document->id}")->assertOk()->assertDownload('devis.pdf');
        $this->assertNotNull($document->fresh()->first_downloaded_at);

        $this->actingAs($other, 'client')->get("/espace-client/documents/{$document->id}")->assertNotFound();
    }

    public function test_a_deactivated_client_loses_access_immediately(): void
    {
        $client = Client::factory()->create();
        $this->actingAs($client, 'client')->get('/espace-client')->assertOk();

        $client->update(['is_active' => false]);
        $this->get('/espace-client')->assertRedirect('/espace-client/connexion');
    }

    public function test_the_team_creates_clients_one_by_one_or_in_bulk_with_an_invitation(): void
    {
        Notification::fake();
        $this->syncPermissions();
        $this->actingAs(User::factory()->commercial()->create());

        $this->post('/admin/clients', ['name' => 'Lycée Askia', 'email' => 'direction@askia.ml', 'phone' => '66 00 00 00', 'invite' => '1'])->assertRedirect();
        Notification::assertSentTo(Client::firstWhere('email', 'direction@askia.ml'), ClientResetPassword::class);

        $this->post('/admin/clients/lot', ['rows' => [
            ['name' => 'Boutique Danaya', 'email' => 'danaya@x.ml', 'invite' => '0'],
            ['name' => 'Doublon', 'email' => 'direction@askia.ml', 'invite' => '0'],
        ]])->assertSessionHasErrors('rows.1.email');
        $this->post('/admin/clients/lot', ['rows' => [['name' => 'Boutique Danaya', 'email' => 'danaya@x.ml', 'invite' => '0']]])->assertRedirect('/admin/clients');
        $this->assertSame(2, Client::count());

        $this->get('/admin/clients')->assertOk()->assertSee('Boutique Danaya');
        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin/clients')->assertForbidden();
    }
}
