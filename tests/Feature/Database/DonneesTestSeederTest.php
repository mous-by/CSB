<?php

namespace Tests\Feature\Database;

use App\Models\Article;
use App\Models\Client;
use App\Models\Demande;
use App\Models\Materiel;
use App\Models\Ticket;
use Database\Seeders\ContenuCsbSeeder;
use Database\Seeders\ContenuInitialSeeder;
use Database\Seeders\DonneesTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/** Jeu de données de test : partout (prix, promos, actualités, clients, demandes, tickets), idempotent, jamais en production. */
class DonneesTestSeederTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    public function test_it_fills_every_screen_and_can_be_run_twice(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->superadmin();
        $this->seed([ContenuInitialSeeder::class, ContenuCsbSeeder::class, DonneesTestSeeder::class, DonneesTestSeeder::class]);

        $this->assertSame(0, Materiel::whereNull('price')->count());
        $this->assertGreaterThan(0, Materiel::onPromo()->count());
        $this->assertSame(3, Article::count());
        $this->assertSame(3, Client::count());
        $this->assertSame(5, Demande::count());
        $this->assertSame(2, Ticket::count());

        $this->get('/')->assertOk()->assertSee('Promo -');
        $this->get('/realisations')->assertOk()->assertSee('MagaStock')->assertSee('SYNEM');
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        // Appel direct : en production, db:seed demanderait d'abord une confirmation interactive.
        $this->app->call([new DonneesTestSeeder, 'run']);
    }
}
