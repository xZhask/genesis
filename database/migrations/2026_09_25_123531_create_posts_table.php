<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Noticias publicadas desde el panel admin.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            // Se fija al crear y no cambia: los enlaces compartidos siguen funcionando
            $table->string('slug', 180)->unique();
            $table->string('excerpt', 280);
            $table->text('body');
            // Ruta base de la portada sin tamaño ni extensión (ver App\Support\ImageResizer)
            $table->string('cover_path')->nullable();
            $table->string('cover_alt', 200)->nullable();
            $table->string('status', 20)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
