<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recursos públicos para acudientes: circulares, listas de útiles,
     * uniformes y horarios. Solo información general (nunca datos personales).
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('title', 160);
            $table->string('summary', 280)->nullable();
            $table->text('body')->nullable();
            // PDF adjunto, guardado tal cual en el disco público
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            // Foto (solo uniformes): ruta base de las versiones WebP (ver ImageResizer)
            $table->string('image_path')->nullable();
            $table->string('image_alt', 200)->nullable();
            // Solo listas de útiles: "Párvulos" … "9.°" (config/school.php)
            $table->string('grade', 20)->nullable();
            $table->unsignedSmallInteger('school_year')->nullable();
            $table->date('published_on');
            $table->string('status', 20)->default('draft');
            // Orden en la página (uniformes y horarios)
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['type', 'status', 'published_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
