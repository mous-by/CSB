<?php

namespace Tests\Feature\System;

use App\Models\User;
use App\Services\Backup\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Livrables d'exploitation : contrôle des sauvegardes (cahier §10, REC-11) et guide d'utilisation en ligne
 * (cahier §14, §15.1 « Transfert »).
 */
class BackupAndDemoTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->dir = storage_path('framework/testing/backups');
        File::deleteDirectory($this->dir);
        config(['backup.path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_backup_verify_accepts_a_complete_dump_and_rejects_a_broken_one(): void
    {
        $this->artisan('backup:verify')->assertFailed(); // aucune sauvegarde

        File::ensureDirectoryExists($this->dir);
        $tables = collect(config('backup.required_tables'))->map(fn ($t) => "CREATE TABLE `{$t}` (id int);")->implode("\n");
        file_put_contents($this->dir.'/db_20261001_020000.sql.gz', gzencode($tables."\n-- Dump completed on 2026-10-01"));
        file_put_contents($this->dir.'/db_20261002_020000.sql.gz', gzencode("CREATE TABLE `users` (id int);\n"));

        $this->assertSame($this->dir.'/db_20261002_020000.sql.gz', app(DatabaseBackup::class)->latest());
        $this->artisan('backup:verify')->assertFailed(); // la plus récente est tronquée
        $this->artisan('backup:verify', ['file' => $this->dir.'/db_20261001_020000.sql.gz'])->assertSuccessful();
    }

    public function test_the_user_guide_covers_the_daily_tasks(): void
    {
        $page = $this->actingAs(User::factory()->commercial()->create())->get('/admin/aide')->assertOk()->assertSee('GUIDE D\'UTILISATION', false);

        foreach (['Se connecter', 'Traiter les demandes', 'Modifier les pages', 'Ajouter des images', 'Publier un logiciel', 'Gérer le matériel', 'Présenter une réalisation', 'Rédiger une actualité', 'Soigner le référencement', 'Gérer les comptes'] as $topic) {
            $page->assertSee($topic);
        }
    }
}
