<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Maquette de l'accueil (CSB, 2026-10-11) : une image par catégorie (cartes « Matériel informatique ») et une accroche
// par logiciel (« Gestion scolaire web et mobile »).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->after('description')->constrained('medias')->nullOnDelete();
        });
        Schema::table('logiciels', function (Blueprint $table) {
            $table->string('tagline', 120)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('logiciels', fn (Blueprint $table) => $table->dropColumn('tagline'));
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
        });
    }
};
