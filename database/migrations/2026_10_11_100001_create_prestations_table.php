<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Services de CSB présentés sur le site (cahier §4 « Services » : vente, réparation, maintenance, développement,
// réseaux, accompagnement).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestations', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 190)->unique();
            $table->string('icon', 60)->default('bx-check-circle');
            $table->string('summary', 300);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
