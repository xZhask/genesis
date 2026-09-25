<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2, capa 2: notas por periodo. Las notas y los logros se guardan por
     * sección y materia (no por docente): si cambia el docente, no se pierden.
     */
    public function up(): void
    {
        // Escala de valoración de cada año (SIEE): rangos, frases de los logros y pesos
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('min_score', 4, 2);
            $table->decimal('max_score', 4, 2);
            $table->decimal('passing_score', 4, 2);
            $table->unsignedTinyInteger('decimals');
            // Límite inferior de cada desempeño (Bajo empieza en el mínimo)
            $table->decimal('basic_from', 4, 2);
            $table->decimal('high_from', 4, 2);
            $table->decimal('superior_from', 4, 2);
            // Frase que encabeza cada logro según el desempeño
            $table->json('phrases');
            // Peso (%) de saber, hacer y ser en la nota del periodo
            $table->decimal('weight_knowing', 5, 2);
            $table->decimal('weight_doing', 5, 2);
            $table->decimal('weight_being', 5, 2);
            $table->timestamps();
        });

        // Actividades evaluadas de una clase en un periodo
        Schema::create('grade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            // knowing | doing | being (saber, hacer, ser)
            $table->string('component', 10);
            $table->string('name', 80);
            $table->unsignedSmallInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['section_id', 'subject_id', 'period_id']);
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->decimal('value', 4, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['grade_item_id', 'enrollment_id']);
        });

        // Los tres logros de la clase en el periodo (saber, hacer y ser)
        Schema::create('period_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->string('component', 10);
            $table->string('text', 300);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['section_id', 'subject_id', 'period_id', 'component'], 'period_objectives_unique');
        });

        // Comportamiento y observaciones del periodo (director de grupo)
        Schema::create('period_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->decimal('behavior', 4, 2)->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'period_id']);
        });

        // Resultados congelados al cerrar el periodo (se borran si se reabre)
        Schema::create('period_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->decimal('knowing', 4, 2)->nullable();
            $table->decimal('doing', 4, 2)->nullable();
            $table->decimal('being', 4, 2)->nullable();
            $table->decimal('score', 4, 2)->nullable();
            $table->string('performance', 10)->nullable();
            $table->unsignedSmallInteger('absences')->default(0);
            $table->timestamps();

            $table->unique(['enrollment_id', 'subject_id', 'period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_results');
        Schema::dropIfExists('period_reports');
        Schema::dropIfExists('period_objectives');
        Schema::dropIfExists('scores');
        Schema::dropIfExists('grade_items');
        Schema::dropIfExists('grading_scales');
    }
};
