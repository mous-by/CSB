<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bibliothèque d'images (cahier §7 « Médias », §9 « stockage organisé, noms sûrs, validation serveur et optimisation »).
// `mediables` : galeries ordonnées (captures d'un logiciel, photos d'un matériel, visuels d'une réalisation).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->string('thumb_path', 255);
            $table->string('original_name', 255);
            $table->string('mime', 50);
            $table->unsignedInteger('size');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->string('alt', 255)->nullable();
            $table->string('folder', 60)->nullable()->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('medias')->restrictOnDelete();
            $table->morphs('mediable');
            $table->unsignedSmallInteger('position')->default(0);
            $table->unique(['media_id', 'mediable_type', 'mediable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('medias');
    }
};
