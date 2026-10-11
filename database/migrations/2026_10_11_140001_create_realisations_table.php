<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Réalisations (cahier §4 « Projets autorisés à être publiés, contexte, besoin, solution, visuels et technologies »).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisations', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 190)->unique();
            $table->string('client_name', 150)->nullable();
            $table->string('sector', 100)->nullable()->index();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('summary', 300);
            $table->text('context')->nullable();
            $table->text('need')->nullable();
            $table->text('solution')->nullable();
            $table->string('technologies', 500)->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->foreignId('logiciel_id')->nullable()->constrained('logiciels')->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->foreignId('seo_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisations');
    }
};
