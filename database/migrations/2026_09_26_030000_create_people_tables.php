<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2, capa 1b: personas. Ingreso con número de documento (el correo
     * pasa a ser opcional y sirve para recuperar la contraseña), estudiantes,
     * acudientes con su parentesco y matrículas por año lectivo.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            // Normalizado: sin puntos, espacios ni guiones, en mayúsculas
            $table->string('document_number', 20)->nullable()->unique()->after('name');
            // Cuentas creadas por el admin con contraseña temporal
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // Cuenta propia solo desde 6.° (config/school.php → academic.student_accounts_from)
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('document_type', 5);
            $table->string('document_number', 20)->unique();
            $table->string('first_names', 80);
            $table->string('last_names', 80);
            $table->date('birth_date')->nullable();
            $table->timestamps();

            $table->index(['last_names', 'first_names']);
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('document_type', 5);
            $table->string('document_number', 20)->unique();
            $table->string('first_names', 80);
            $table->string('last_names', 80);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->index(['last_names', 'first_names']);
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // App\Enums\GuardianRelationship (madre, padre, abuela o abuelo…)
            $table->string('relationship', 20);
            // Acudiente principal: a quien el colegio llama primero
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // Una sección con estudiantes matriculados no se puede eliminar
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->string('status', 12)->default('active');
            $table->date('withdrawn_on')->nullable();
            $table->timestamps();

            // Una matrícula por estudiante y año; cambiar de sección la actualiza
            $table->unique(['student_id', 'school_year_id']);
            $table->index(['section_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->dropColumn(['document_number', 'must_change_password', 'last_login_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
