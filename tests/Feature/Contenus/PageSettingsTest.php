<?php

namespace Tests\Feature\Contenus;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Pages et paramètres (cahier §7) : textes des pages pour le gestionnaire de contenu, paramètres sensibles pour
 * l'administrateur ; chaque modification est auditée et visible sur le site.
 */
class PageSettingsTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_the_gestionnaire_edits_pages_but_not_sensitive_settings(): void
    {
        $gestionnaire = User::factory()->gestionnaire()->create();

        $this->actingAs($gestionnaire)->get('/admin/pages')->assertOk()
            ->assertSee('Pages et paramètres')->assertSee('ACCUEIL')->assertSee('À propos')->assertDontSee('Coordonnées');
        $this->get('/admin/pages?onglet=coordonnees')->assertOk()->assertDontSee('Téléphone principal');

        $this->put('/admin/pages/coordonnees', ['contact__phone_1' => '70 00 00 00'])->assertForbidden();
        $this->put('/admin/pages/accueil', ['home__hero_title' => 'Votre partenaire informatique', 'home__hero_text' => 'Texte'])
            ->assertRedirect('/admin/pages?onglet=accueil');

        $this->assertSame('Votre partenaire informatique', app(SiteSettings::class)->get('home.hero_title'));
        $this->get('/')->assertSee('Votre partenaire')->assertSee('<span class="accent">informatique</span>', false);
    }

    public function test_the_commercial_has_no_access(): void
    {
        $this->actingAs(User::factory()->commercial()->create())->get('/admin/pages')->assertForbidden();
    }

    public function test_the_administrateur_changes_the_coordinates_with_validation_and_audit(): void
    {
        $this->actingAs(User::factory()->administrateur()->create());

        $this->from('/admin/pages?onglet=coordonnees')->put('/admin/pages/coordonnees', [
            'contact__phone_1' => 'abc', 'contact__email' => 'pas-un-email', 'contact__map_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['contact__phone_1', 'contact__email', 'contact__map_url']);

        $this->put('/admin/pages/coordonnees', [
            'contact__phone_1' => '74 74 56 69', 'contact__phone_2' => '', 'contact__whatsapp' => '74 74 56 69',
            'contact__email' => 'contact@csb.test', 'contact__address' => "Bamako\nQuartier du fleuve", 'contact__hours' => '', 'contact__map_url' => '',
        ])->assertSessionHasNoErrors();

        $settings = app(SiteSettings::class);
        $this->assertSame('contact@csb.test', $settings->get('contact.email'));
        $this->assertSame('', $settings->get('contact.phone_2'), 'Un champ vidé reste vide sur le site.');
        $this->assertFalse(AppSetting::where('key', 'contact.phone_1')->exists(), 'Une valeur égale au défaut ne crée pas de surcharge.');
        $this->assertTrue(ActivityLog::where('module', 'site')->where('action', 'site.created')->exists());

        $this->get('/')->assertSee('contact@csb.test')->assertDontSee('67 20 57 36')->assertSee('Quartier du fleuve');
    }

    public function test_several_payment_numbers_can_be_entered_separated_by_a_slash(): void
    {
        $this->actingAs(User::factory()->administrateur()->create());

        $this->put('/admin/pages/boutique', ['shop__wave' => '74 74 56 69 / 12'])->assertSessionHasErrors('shop__wave');
        $this->put('/admin/pages/boutique', ['shop__wave' => '74 74 56 69 / 67 20 57 36', 'shop__orange_money' => '74 74 56 69'])->assertSessionHasNoErrors();

        $this->assertSame('74 74 56 69 / 67 20 57 36', app(SiteSettings::class)->get('shop.wave'));
    }

    public function test_an_unknown_tab_is_a_404(): void
    {
        $this->actingAs(User::factory()->administrateur()->create())->put('/admin/pages/inconnu', [])->assertNotFound();
    }

    public function test_markdown_content_cannot_inject_html(): void
    {
        $this->actingAs(User::factory()->administrateur()->create())->put('/admin/pages/legal', [
            'legal__mentions' => "## Éditeur\n\n<script>alert('x')</script>\n\n[piège](javascript:alert(1))",
            'legal__privacy' => 'Texte',
        ])->assertSessionHasNoErrors();

        $page = $this->get('/mentions-legales')->assertOk()->assertSee('Éditeur')->getContent();
        $this->assertStringNotContainsString("<script>alert('x')</script>", $page);
        $this->assertStringNotContainsString('javascript:alert', $page);
    }
}
