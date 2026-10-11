<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Espace client (cahier §15.3 « Espace client avec suivi des demandes et documents », demandé par CSB le
// 2026-10-11) : comptes clients (garde `client`), documents mis à disposition (disque privé), lien des demandes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('organization', 150)->nullable();
            $table->string('email', 190)->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('address', 300)->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('title', 200);
            $table->string('type', 20)->default('autre');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_downloaded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('demandes', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('assigned_to')->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demandes', fn (Blueprint $table) => $table->dropConstrainedForeignId('client_id'));
        Schema::dropIfExists('client_documents');
        Schema::dropIfExists('client_password_reset_tokens');
        Schema::dropIfExists('clients');
    }
};
