<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Actualités et conseils (cahier §4 « Articles, annonces et conseils administrables, avec dates, catégories et
// métadonnées SEO »). Une date de publication future programme l'article.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 190)->unique();
            $table->string('excerpt', 400)->nullable();
            $table->longText('body');
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->foreignId('seo_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
