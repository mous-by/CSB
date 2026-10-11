<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Demandes commerciales (cahier §6, §9.1 « avec statut et dates » ; « ne doivent pas être supprimées par cascade à la
// suite d'une suppression de contenu ») : le contenu concerné est référencé par un lien polymorphe sans contrainte et
// son nom est figé (`subject_label`). Notes internes réservées au personnel autorisé.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('status', 20)->default('nouvelle')->index();
            $table->string('name', 150);
            $table->string('organization', 150)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('subject', 200)->nullable();
            $table->text('message')->nullable();
            $table->string('need_type', 20)->nullable();
            $table->string('budget', 100)->nullable();
            $table->string('deadline', 100)->nullable();
            $table->string('availability', 300)->nullable();
            $table->nullableMorphs('subject');
            $table->string('subject_label', 200)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->unsignedInteger('attachment_size')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('closed_reason', 500)->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->string('notification_error', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });

        Schema::create('demande_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_id')->constrained('demandes')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_notes');
        Schema::dropIfExists('demandes');
    }
};
