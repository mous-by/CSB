<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Témoignages de clients affichés sur l'accueil, seulement s'ils sont autorisés à la publication (cahier §4).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temoignages', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 150);
            $table->string('author_title', 150)->nullable();
            $table->string('organization', 150)->nullable();
            $table->text('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temoignages');
    }
};
