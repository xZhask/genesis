<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2, capa 1a: estructura académica. Años lectivos con sus periodos
     * (cierre y reapertura registrados), grados, secciones, áreas, materias
     * y plan de estudios por grado.
     */
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->date('starts_on');
            $table->date('ends_on');
            // Porcentaje del periodo en la nota final (provisional: 25 % cada uno)
            $table->decimal('weight', 5, 2);
            $table->string('status', 10)->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_year_id', 'number']);
        });

        // Historial de cierres y reaperturas (regla de seguridad 3)
        Schema::create('period_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->string('status', 10);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            // preschool | primary | secondary (config/school.php → levels)
            $table->string('level', 20);
            $table->string('name', 20)->unique();
            $table->unsignedTinyInteger('position')->unique();
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            // "1", "2", "A"… se muestra como "3.° 1"
            $table->string('name', 10);
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['grade_id', 'school_year_id', 'name']);
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->string('name', 80)->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // Plan de estudios: qué materias ve cada grado y cuántas horas semanales (IH)
        Schema::create('grade_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('weekly_hours');
            $table->timestamps();

            $table->unique(['grade_id', 'subject_id']);
        });

        // Los 13 grados del colegio son fijos: se crean aquí (en producción no se ejecuta db:seed)
        $position = 0;
        foreach (config('school.levels') as $level) {
            foreach ($level['grades'] as $grade) {
                DB::table('grades')->insert([
                    'level' => $level['key'],
                    'name' => $grade,
                    'position' => ++$position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_subject');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('period_status_changes');
        Schema::dropIfExists('periods');
        Schema::dropIfExists('school_years');
    }
};
