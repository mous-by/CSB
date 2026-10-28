<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fréquentation du site (demande de CSB, 2026-10-28 ; cahier §2 « visites, pages les plus consultées, conversion ») :
// une ligne par page vue d'un visiteur humain. Aucune donnée personnelle : le visiteur est une empreinte anonyme qui
// change chaque jour (ni adresse IP, ni cookie). Lignes de plus de 13 mois supprimées (`stats:purge`).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_vues', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->char('visitor', 32);
            $table->string('path', 255);
            $table->string('route', 100)->nullable();
            $table->string('source', 20);
            $table->string('referrer_host', 150)->nullable();
            $table->string('device', 12);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['day', 'visitor']);
            $table->index(['day', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_vues');
    }
};
