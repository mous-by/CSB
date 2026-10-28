<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Conseiller d'achat (demande de CSB, 2026-10-28) : l'équipe indique pour chaque ordinateur les usages qu'il couvre et
// son format ; le conseiller du site propose ensuite les modèles adaptés au métier et au budget du visiteur.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiels', function (Blueprint $table) {
            $table->text('advisor_usages')->nullable()->after('specs');
            $table->string('advisor_form', 20)->nullable()->after('advisor_usages');
        });
    }

    public function down(): void
    {
        Schema::table('materiels', fn (Blueprint $table) => $table->dropColumn(['advisor_usages', 'advisor_form']));
    }
};
