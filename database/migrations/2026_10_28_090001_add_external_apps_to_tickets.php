<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tickets venus des applications de CSB (KalanNet, DojoManager…) par l'API (demande de CSB, 2026-10-28) : l'utilisateur
// de l'application n'a pas de compte client CSB, ses coordonnées sont gardées sur le ticket. Les jetons d'API ont une
// portée : « stock » (synchronisation) ou « tickets » (application connectée).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->string('scope', 20)->default('stock')->after('name')->index();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->change();
            $table->string('source_app', 100)->nullable()->after('client_id')->index();
            $table->foreignId('api_token_id')->nullable()->after('source_app')->constrained('api_tokens')->nullOnDelete();
            $table->string('reporter_ref', 100)->nullable()->after('api_token_id');
            $table->string('reporter_name', 150)->nullable()->after('reporter_ref');
            $table->string('reporter_role', 60)->nullable()->after('reporter_name');
            $table->string('reporter_org', 150)->nullable()->after('reporter_role');
            $table->string('reporter_email', 190)->nullable()->after('reporter_org');
            $table->string('reporter_phone', 20)->nullable()->after('reporter_email');
            $table->index(['source_app', 'reporter_ref']);
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->boolean('from_reporter')->default(false)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_messages', fn (Blueprint $table) => $table->dropColumn('from_reporter'));
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['source_app', 'reporter_ref']);
            $table->dropConstrainedForeignId('api_token_id');
            $table->dropColumn(['source_app', 'reporter_ref', 'reporter_name', 'reporter_role', 'reporter_org', 'reporter_email', 'reporter_phone']);
        });
        Schema::table('api_tokens', fn (Blueprint $table) => $table->dropColumn('scope'));
    }
};
