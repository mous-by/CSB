<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Redirections permanentes (cahier §11.1 « Redirections propres lors d'un changement d'URL ») : créées automatiquement
// quand l'adresse d'un contenu change (HasSlug), suivies par la route de repli du site public.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirections', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 255)->unique();
            $table->string('to_path', 255);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirections');
    }
};
