<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catalogue des logiciels (cahier §5.1, §5.2). `features` et `platforms` : listes JSON stockées en texte (MariaDB 10.4,
// aucune requête JSON). `categorie_logiciel` : catégories secondaires.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logiciels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 190)->unique();
            $table->string('summary', 300);
            $table->text('problem')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->text('features')->nullable();
            $table->string('platforms', 255)->nullable();
            $table->string('status', 30)->default('disponible')->index();
            $table->string('price_text', 150)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->foreignId('logo_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->foreignId('seo_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'status', 'sort_order']);
        });

        Schema::create('categorie_logiciel', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('logiciel_id')->constrained('logiciels')->restrictOnDelete();
            $table->primary(['category_id', 'logiciel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_logiciel');
        Schema::dropIfExists('logiciels');
    }
};
