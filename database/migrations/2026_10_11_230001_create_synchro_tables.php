<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Synchronisation du stock (cahier §15.3) : référence et quantité du matériel envoyées par le logiciel de stock de CSB
// (API à jeton) ou importées d'un fichier ; chaque synchronisation est journalisée.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiels', function (Blueprint $table) {
            $table->string('reference', 60)->nullable()->unique()->after('slug');
            $table->integer('stock_quantity')->nullable()->after('availability');
            $table->timestamp('stock_synced_at')->nullable()->after('stock_quantity');
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('token_hash', 64)->unique();
            $table->string('last_four', 4);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('api_token_id')->nullable()->constrained('api_tokens')->nullOnDelete();
            $table->string('file_name', 200)->nullable();
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->text('unknown_references')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_syncs');
        Schema::dropIfExists('api_tokens');
        Schema::table('materiels', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn(['reference', 'stock_quantity', 'stock_synced_at']);
        });
    }
};
