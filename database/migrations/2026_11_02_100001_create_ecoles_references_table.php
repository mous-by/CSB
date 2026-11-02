<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Écoles qui utilisent une application connectée (KalanNet) : bande « Ils nous font confiance » de l'accueil. */
    public function up(): void
    {
        Schema::create('ecoles_references', function (Blueprint $table) {
            $table->id();
            $table->string('source_app', 120);
            $table->string('ref', 64);
            $table->string('name', 200);
            $table->string('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->char('logo_hash', 32)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['source_app', 'ref']);
            $table->index('is_visible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecoles_references');
    }
};
