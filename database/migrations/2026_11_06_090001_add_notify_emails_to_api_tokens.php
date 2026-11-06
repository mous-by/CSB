<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Application connectée : adresses e-mail prévenues aussitôt à chaque ticket de cette application (nouveau ticket,
// réponse du demandeur), en plus du destinataire habituel de l'équipe (demande de CSB, 2026-11-06). Réglées dans
// Tickets → Applications connectées, jamais dans le code.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->text('notify_emails')->nullable()->after('scope');
        });
    }

    public function down(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn('notify_emails');
        });
    }
};
