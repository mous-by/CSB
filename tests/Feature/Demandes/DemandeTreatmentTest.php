<?php

namespace Tests\Feature\Demandes;

use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Models\ActivityLog;
use App\Models\Demande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Traitement des demandes (cahier §6.5, §7) : consulter, filtrer, changer les statuts, attribuer, notes internes,
 * export ; réservé au personnel autorisé.
 */
class DemandeTreatmentTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $commercial;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->commercial = User::factory()->commercial()->create(['name' => 'Commercial CSB']);
    }

    public function test_the_commercial_treats_a_request_from_new_to_won(): void
    {
        $demande = Demande::factory()->of(DemandeType::Devis)->create(['name' => 'Lycée Askia']);

        $this->actingAs($this->commercial)->get('/admin/demandes')->assertOk()->assertSee('Lycée Askia')->assertSee('Nouvelle');
        $this->get("/admin/demandes/{$demande->id}")->assertOk()->assertSee('NOTES INTERNES')->assertSee($demande->reference());

        $this->put("/admin/demandes/{$demande->id}/statut", ['status' => 'en_cours'])->assertRedirect("/admin/demandes/{$demande->id}");
        $demande->refresh();
        $this->assertSame(DemandeStatus::EnCours, $demande->status);
        $this->assertSame($this->commercial->id, $demande->assigned_to, 'Prise en charge : attribuée automatiquement.');

        $this->put("/admin/demandes/{$demande->id}/statut", ['status' => 'devis_envoye']);
        $this->put("/admin/demandes/{$demande->id}/statut", ['status' => 'gagnee']);
        $this->assertSame(DemandeStatus::Gagnee, $demande->fresh()->status);
        $this->assertSame(3, ActivityLog::where('action', 'demandes.status_changed')->where('subject_id', $demande->id)->count());

        $this->put("/admin/demandes/{$demande->id}/statut", ['status' => 'perdue', 'closed_reason' => 'Doublon']);
        $this->assertSame('Doublon', $demande->fresh()->closed_reason);
        $this->put("/admin/demandes/{$demande->id}/statut", ['status' => 'inconnu'])->assertSessionHasErrors('status');
    }

    public function test_internal_notes_are_added_with_ajax(): void
    {
        $demande = Demande::factory()->create();

        $this->actingAs($this->commercial)->postJson("/admin/demandes/{$demande->id}/notes", ['body' => 'Rappeler jeudi <b>matin</b>'])
            ->assertOk()->assertJsonPath('count', 1)->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'Rappeler jeudi &lt;b&gt;matin&lt;/b&gt;'))->etc());
        $this->postJson("/admin/demandes/{$demande->id}/notes", ['body' => ''])->assertUnprocessable();
        $this->get("/admin/demandes/{$demande->id}")->assertSee('Rappeler jeudi');
    }

    public function test_the_export_is_a_csv_for_excel_and_is_audited(): void
    {
        Demande::factory()->create(['name' => 'Client Export']);

        $response = $this->actingAs($this->commercial)->get('/admin/demandes/export?type=contact');
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Client Export', $csv);
        $this->assertStringContainsString(';', $csv);
        $this->assertTrue(ActivityLog::where('action', 'demandes.exported')->exists());
    }

    public function test_attachments_are_downloaded_by_authorized_staff_only(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demandes/2026/10/x.pdf', '%PDF');
        $demande = Demande::factory()->create(['attachment_path' => 'demandes/2026/10/x.pdf', 'attachment_name' => 'cahier.pdf', 'attachment_size' => 4]);

        $this->get("/admin/demandes/{$demande->id}/piece-jointe")->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->gestionnaire()->create())->get("/admin/demandes/{$demande->id}/piece-jointe")->assertForbidden();
        $this->actingAs($this->commercial)->get("/admin/demandes/{$demande->id}/piece-jointe")->assertOk()->assertDownload('cahier.pdf');
    }

    public function test_the_content_manager_and_the_commercial_have_distinct_rights(): void
    {
        $demande = Demande::factory()->create();

        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin/demandes')->assertForbidden();
        $this->actingAs($this->commercial)->delete("/admin/demandes/{$demande->id}")->assertForbidden();
        $this->actingAs(User::factory()->administrateur()->create())->delete("/admin/demandes/{$demande->id}")->assertRedirect('/admin/demandes');
        $this->assertSoftDeleted($demande);
    }

    public function test_the_dashboard_summarises_requests_for_the_commercial(): void
    {
        Demande::factory()->count(2)->create();
        Demande::factory()->create(['status' => DemandeStatus::Gagnee, 'status_changed_at' => now()]);

        $this->actingAs($this->commercial)->get('/admin')->assertOk()
            ->assertSee('Nouvelles demandes')->assertSee('DEMANDES DES 14 DERNIERS JOURS')->assertSee('DERNIÈRES DEMANDES')
            ->assertSee('2 nouvelle(s) demande(s) à traiter.')->assertSee('nv-badge', false)
            ->assertDontSee('Comptes et accès');
    }
}
