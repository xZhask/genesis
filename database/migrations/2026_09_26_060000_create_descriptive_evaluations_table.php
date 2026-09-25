<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2, capa 3b: preescolar se evalúa de forma cualitativa y
     * descriptiva, sin notas. La docente escribe una descripción por
     * dimensión del desarrollo para cada niño en cada periodo (provisional:
     * las siete dimensiones del Decreto 2247 de 1997, hasta tener el modelo
     * de boletín del colegio).
     */
    public function up(): void
    {
        Schema::create('descriptive_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            // Dimensión del desarrollo (materia del plan de estudios de preescolar)
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->text('text');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'subject_id', 'period_id'], 'descriptive_evaluations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descriptive_evaluations');
    }
};
