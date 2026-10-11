<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adresse e-mail confirmée : condition pour rattacher au compte les demandes faites avant l'inscription avec cette
// adresse (sinon quiconque saisirait l'e-mail d'un autre verrait ses demandes).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('clients', fn (Blueprint $table) => $table->dropColumn('email_verified_at'));
    }
};
