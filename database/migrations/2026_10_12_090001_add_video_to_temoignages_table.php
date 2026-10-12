<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Témoignages en vidéo (demande de CSB, 2026-10-12) : vidéo hébergée par le site (convertie en MP4 avec une image
// d'aperçu si ffmpeg est présent) ou lien externe, photo et note de l'auteur, témoignages envoyés par les clients
// eux-mêmes (masqués jusqu'à la modération, avec leur accord de publication).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temoignages', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
            $table->foreignId('photo_media_id')->nullable()->after('organization')->constrained('medias')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->nullable()->after('photo_media_id');
            $table->string('video_path', 255)->nullable()->after('content');
            $table->string('video_poster_path', 255)->nullable()->after('video_path');
            $table->unsignedSmallInteger('video_duration')->nullable()->after('video_poster_path');
            $table->string('video_status', 20)->nullable()->after('video_duration');
            $table->string('video_error', 500)->nullable()->after('video_status');
            $table->string('video_url', 500)->nullable()->after('video_error');
            $table->string('source', 20)->default('equipe')->after('video_url');
            $table->string('contact', 150)->nullable()->after('source');
            $table->foreignId('client_id')->nullable()->after('contact')->constrained('clients')->nullOnDelete();
            $table->timestamp('consent_at')->nullable()->after('client_id');
            $table->boolean('is_featured')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('temoignages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photo_media_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['rating', 'video_path', 'video_poster_path', 'video_duration', 'video_status', 'video_error', 'video_url', 'source', 'contact', 'consent_at', 'is_featured']);
        });
    }
};
