<?php

namespace Tests\Feature\Modules;

use App\Models\ApiToken;
use App\Models\EcoleReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Bande de l'accueil : écoles envoyées par une application connectée, logos, retrait, cloisonnement entre applications. */
class EcolesReferencesTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/references/ecoles';

    private function jeton(string $nom = 'KalanNet'): array
    {
        [, $jeton] = ApiToken::issue($nom, null, ApiToken::SCOPE_TICKETS);

        return ['Authorization' => 'Bearer '.$jeton, 'Accept' => 'application/json'];
    }

    /** Écoles déjà reçues avec un logo (la bande ne montre que celles-là). */
    private function avecLogo(string ...$refs): void
    {
        EcoleReference::whereIn('ref', $refs)->update(['logo_path' => 'references/ecoles/test.png', 'logo_hash' => md5('x')]);
        Cache::forget(EcoleReference::CACHE_KEY);
    }

    private function ligne(string $ref, array $autres = []): array
    {
        return $autres + ['ref' => $ref, 'nom' => 'École '.$ref, 'adresse' => 'Ségou', 'logo_hash' => null, 'visible' => true];
    }

    public function test_seule_une_ecole_avec_logo_apparait_et_des_que_son_logo_arrive(): void
    {
        Storage::fake('public');
        $entetes = $this->jeton();

        $this->get('/')->assertOk()->assertDontSee('Ils nous font confiance');

        $hash = md5('logo');
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('1', ['nom' => 'Lycée Modèle de Ségou']), $this->ligne('2', ['logo_hash' => $hash])]], $entetes)
            ->assertOk()->assertExactJson(['logos_a_envoyer' => ['2']]);

        // Aucune n'a encore de logo : pas de bande.
        $this->get('/')->assertOk()->assertDontSee('Ils nous font confiance')->assertDontSee('Lycée Modèle de Ségou');

        $this->post(self::URL.'/2/logo', ['logo' => UploadedFile::fake()->image('logo.jpg', 800, 400), 'logo_hash' => $hash], $entetes)->assertOk();
        $this->get('/')->assertOk()->assertSee('Ils nous font confiance')->assertSee('École 2')->assertSee('Une école gère')->assertDontSee('Lycée Modèle de Ségou');
        $ecole = EcoleReference::where('ref', '2')->first();
        Storage::disk('public')->assertExists($ecole->logo_path);
        [$largeur] = getimagesizefromstring(Storage::disk('public')->get($ecole->logo_path));
        $this->assertSame(240, $largeur);
        $this->get('/')->assertSee('storage/'.$ecole->logo_path, false);

        // Même empreinte : logo non redemandé. Logo retiré dans l'application : retiré ici.
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('2', ['logo_hash' => $hash])]], $entetes)->assertExactJson(['logos_a_envoyer' => []]);
        $chemin = $ecole->logo_path;
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('2')]], $entetes)->assertOk();
        Storage::disk('public')->assertMissing($chemin);
        $this->get('/')->assertDontSee('Ils nous font confiance');
    }

    public function test_retrait_d_une_ecole_et_liste_complete(): void
    {
        $entetes = $this->jeton();
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('1'), $this->ligne('2'), $this->ligne('3')]], $entetes)->assertOk();
        $this->avecLogo('1', '2', '3');

        $this->postJson(self::URL, ['ecoles' => [$this->ligne('1', ['visible' => false])]], $entetes)->assertOk();
        $this->get('/')->assertDontSee('École 1')->assertSee('École 2')->assertSee('École 3');

        // Liste complète : les absentes sortent de la bande (abonnement expiré).
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('3', ['logo_hash' => md5('x')])], 'complet' => true], $entetes)->assertOk();
        $this->get('/')->assertDontSee('École 2')->assertSee('École 3');

        // Une école retirée et inconnue n'est pas créée.
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('9', ['visible' => false])]], $entetes)->assertOk();
        $this->assertDatabaseMissing('ecoles_references', ['ref' => '9']);
    }

    public function test_acces_cloisonne_et_contenu_echappe(): void
    {
        $this->postJson(self::URL, ['ecoles' => []], ['Accept' => 'application/json'])->assertUnauthorized();
        [, $stock] = ApiToken::issue('Stock', null, ApiToken::SCOPE_STOCK);
        $this->postJson(self::URL, ['ecoles' => []], ['Authorization' => 'Bearer '.$stock, 'Accept' => 'application/json'])->assertUnauthorized();

        $kalannet = $this->jeton();
        $autre = $this->jeton('Autre appli');
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('1', ['nom' => '<script>alert(1)</script>'])]], $kalannet)->assertOk();
        $this->avecLogo('1');
        $this->get('/')->assertDontSee('<script>alert(1)</script>', false);

        // L'autre application : même référence, autre école ; sa liste complète ne retire pas celles de KalanNet.
        $this->postJson(self::URL, ['ecoles' => [], 'complet' => true], $autre)->assertOk();
        $this->assertTrue(EcoleReference::where('source_app', 'KalanNet')->first()->is_visible);
        $this->post(self::URL.'/1/logo', ['logo' => UploadedFile::fake()->image('x.png'), 'logo_hash' => md5('x')], $autre)->assertNotFound();
    }

    public function test_beaucoup_d_ecoles_la_nouvelle_toujours_visible_et_le_vrai_total(): void
    {
        $entetes = $this->jeton();
        $lignes = array_map(fn ($i) => $this->ligne((string) $i), range(1, 150));
        $this->postJson(self::URL, ['ecoles' => $lignes], $entetes)->assertOk();
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('151', ['nom' => 'Toute Nouvelle Ecole'])]], $entetes)->assertOk();
        EcoleReference::query()->update(['logo_path' => 'references/ecoles/test.png']);
        Cache::forget(EcoleReference::CACHE_KEY);

        $page = $this->get('/')->assertOk()->assertSee('151 écoles gèrent')->assertSee('Toute Nouvelle Ecole');
        $this->assertCount(80, $page->viewData('ecolesReferences')['ecoles']);
    }

    public function test_la_bande_fonctionne_avec_le_cache_en_base_de_la_production(): void
    {
        config(['cache.default' => 'database']);
        $this->postJson(self::URL, ['ecoles' => [$this->ligne('1')]], $this->jeton())->assertOk();
        $this->avecLogo('1');

        $this->get('/')->assertOk()->assertSee('École 1');
        $this->get('/')->assertOk()->assertSee('École 1'); // relue depuis le cache
    }
}
