<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Horario de clases: fijo para todo el año lectivo.
     * - schedule_blocks: franjas (horas de clase y descansos) por nivel, porque
     *   preescolar, primaria y secundaria pueden tener horas distintas.
     * - schedule_slots: qué materia ve cada sección en cada franja y día. El
     *   docente no se guarda: sale de las asignaciones docentes.
     */
    public function up(): void
    {
        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20); // preschool | primary | secondary
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_break')->default(false);
            $table->string('label', 40)->nullable(); // «Descanso», «Almuerzo»
            $table->timestamps();

            $table->index(['school_year_id', 'level']);
        });

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_block_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1 = lunes … 7 = domingo (ISO)
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['section_id', 'schedule_block_id', 'weekday'], 'schedule_slots_unique');
            $table->index(['weekday', 'schedule_block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_slots');
        Schema::dropIfExists('schedule_blocks');
    }
};
