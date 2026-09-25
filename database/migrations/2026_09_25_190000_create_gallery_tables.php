<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Galería: álbumes por actividad y sus fotos.
     */
    public function up(): void
    {
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            // Se fija al crear y no cambia: los enlaces compartidos siguen funcionando
            $table->string('slug', 140)->unique();
            $table->string('description', 500)->nullable();
            // Fecha de la actividad (no la de subida): ordena la galería
            $table->date('taken_on');
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('cover_photo_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'taken_on']);
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_album_id')->constrained()->cascadeOnDelete();
            // Ruta base sin tamaño ni extensión (ver App\Support\ImageResizer)
            $table->string('path');
            // Vacía = aún sin describir; se muestra "Foto de {álbum}"
            $table->string('alt', 200)->nullable();
            $table->string('caption', 200)->nullable();
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['gallery_album_id', 'position']);
        });

        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->foreign('cover_photo_id')->references('id')->on('gallery_photos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->dropForeign(['cover_photo_id']);
        });
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('gallery_albums');
    }
};
