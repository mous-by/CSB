<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Abonnés aux actualités (maquette CSB 2026-10-11 : « Restez informé de nos actualités »). Désinscription par jeton
// unique ; un abonné désinscrit est conservé (preuve de son choix) et ne reçoit plus rien. `newsletter_sent_at` sur les
// articles : date d'envoi aux abonnés (un article ne s'envoie qu'une fois).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnes', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('name', 150)->nullable();
            $table->string('token', 64)->unique();
            $table->string('source', 30)->default('site');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->timestamp('newsletter_sent_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('newsletter_sent_at'));
        Schema::dropIfExists('abonnes');
    }
};
