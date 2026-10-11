<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Prix promotionnel d'un matériel (demande de CSB, 2026-10-11) : le prix normal est barré tant que la promotion court.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiels', function (Blueprint $table) {
            $table->decimal('promo_price', 12, 0)->nullable()->after('price');
            $table->date('promo_ends_on')->nullable()->after('promo_price');
        });
    }

    public function down(): void
    {
        Schema::table('materiels', function (Blueprint $table) {
            $table->dropColumn(['promo_price', 'promo_ends_on']);
        });
    }
};
