<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2, capa 1c: qué docente dicta cada materia en cada sección y la
     * asistencia por materia (como en el boletín actual: inasistencias por
     * asignatura).
     */
    public function up(): void
    {
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            // Una materia de una sección la dicta un solo docente
            $table->unique(['section_id', 'subject_id']);
            $table->index('teacher_id');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            // Con asistencia registrada, la matrícula ya no se puede borrar (se marca retirada)
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            // Se guarda el periodo para validar el cierre y sumar por periodo
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->date('date');
            // present | absent | late | excused
            $table->string('status', 10);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'subject_id', 'date']);
            $table->index(['subject_id', 'date']);
            $table->index(['enrollment_id', 'period_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('teacher_assignments');
    }
};
