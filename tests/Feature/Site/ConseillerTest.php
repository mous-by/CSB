<?php

namespace Tests\Feature\Site;

use App\Enums\AdvisorForm;
use App\Enums\Availability;
use App\Enums\CategoryType;
use App\Enums\MaterielCondition;
use App\Models\Category;
use App\Models\Materiel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Conseiller d'achat : l'équipe coche les usages d'un ordinateur, le visiteur répond aux questions et voit les modèles
 * adaptés à son métier, son format, son budget et l'état souhaité, avec les raisons du choix.
 */
class ConseillerTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    public function test_the_team_ticks_the_usages_of_a_computer(): void
    {
        $this->syncPermissions();
        $portables = Category::factory()->of(CategoryType::Materiel)->create();
        $this->actingAs(User::factory()->gestionnaire()->create())->get('/admin/materiels/create')->assertOk()->assertSee('CONSEILLER D\'ACHAT', false);

        $this->post('/admin/materiels', [
            'designation' => 'HP ProBook 450', 'category_id' => $portables->id, 'condition' => 'neuf', 'availability' => 'disponible', 'sort_order' => 0,
            'advisor_usages' => ['gestion', 'bureautique'], 'advisor_form' => 'portable',
        ])->assertRedirect();
        $materiel = Materiel::where('designation', 'HP ProBook 450')->firstOrFail();
        $this->assertSame(['gestion', 'bureautique'], $materiel->advisor_usages);
        $this->assertSame(AdvisorForm::Portable, $materiel->advisor_form);

        $this->post('/admin/materiels', ['designation' => 'X', 'category_id' => $portables->id, 'condition' => 'neuf', 'availability' => 'disponible', 'sort_order' => 0, 'advisor_usages' => ['cuisine']])
            ->assertSessionHasErrors('advisor_usages.0');
    }

    public function test_the_visitor_gets_computers_matching_job_form_budget_and_condition(): void
    {
        $make = fn (string $name, array $usages, ?int $price, array $more = []) => Materiel::factory()->create($more + ['designation' => $name, 'advisor_usages' => $usages, 'advisor_form' => AdvisorForm::Portable, 'price' => $price]);
        $make('Portable Gestion 300', ['gestion', 'bureautique'], 300000);
        $make('Portable Gestion Reco', ['gestion', 'bureautique'], 220000, ['condition' => MaterielCondition::Reconditionne]);
        $make('Portable Bureautique', ['bureautique'], 180000);
        $make('Station Graphisme', ['creation'], 900000);
        $make('Portable Trop Cher', ['gestion', 'bureautique'], 700000);
        $make('Portable Indisponible', ['gestion'], 200000, ['availability' => Availability::Indisponible]);
        $make('Tour Gestion', ['gestion', 'bureautique'], 250000, ['advisor_form' => AdvisorForm::Bureau]);
        Materiel::factory()->create(['designation' => 'Portable Sans Usage', 'price' => 200000]);

        $this->get('/choisir-mon-ordinateur')->assertOk()->assertSee('Quel est votre métier')->assertDontSee('Nos ordinateurs qui vous conviennent');
        $this->get('/materiel')->assertOk()->assertSee('Me laisser guider');

        $response = $this->get('/choisir-mon-ordinateur?profil=comptable&format=portable&budget=250-400&etat=tous&niveau=debutant')->assertOk()
            ->assertSee('Nos ordinateurs qui vous conviennent')->assertSee('Les mots à connaître')->assertSee('Notre meilleur choix pour vous')
            ->assertSee('Portable Gestion 300')->assertSee('Portable Gestion Reco')->assertSee('Portable Bureautique')
            ->assertDontSee('Station Graphisme')->assertDontSee('Portable Trop Cher')->assertDontSee('Portable Indisponible')->assertDontSee('Tour Gestion')->assertDontSee('Portable Sans Usage')
            ->assertSee('Moins adapté pour : Comptabilité et logiciels de gestion');
        // Le plus adapté d'abord : couverture complète, puis prix.
        $response->assertSeeInOrder(['Portable Gestion Reco', 'Portable Gestion 300', 'Portable Bureautique']);

        $this->get('/choisir-mon-ordinateur?profil=comptable&format=portable&budget=250-400&etat=neuf&niveau=avance')->assertOk()
            ->assertDontSee('Portable Gestion Reco')->assertDontSee('Les mots à connaître');

        $this->get('/choisir-mon-ordinateur?profil=createur&budget=moins-150&etat=tous')->assertOk()
            ->assertSee('Aucun modèle de notre catalogue')->assertSee('900 000 FCFA');
    }
}
