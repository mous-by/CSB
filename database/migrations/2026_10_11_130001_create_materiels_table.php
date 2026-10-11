<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catalogue de matériel informatique (cahier §5.3, §5.4). Informatif : prix et disponibilité déclarés à la main, jamais
// synchronisés avec un stock (D-5). `specs` : caractéristiques [[libellé, valeur], …] en JSON stocké en texte.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->string('designation', 200);
            $table->string('slug', 190)->unique();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('brand', 100)->nullable();
            $table->string('model', 150)->nullable();
            $table->string('summary', 300)->nullable();
            $table->text('description')->nullable();
            $table->text('specs')->nullable();
            $table->string('condition', 20)->default('neuf')->index();
            $table->decimal('price', 12, 0)->nullable();
            $table->string('price_conditions', 200)->nullable();
            $table->string('availability', 20)->default('disponible')->index();
            $table->foreignId('main_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->foreignId('seo_media_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};
