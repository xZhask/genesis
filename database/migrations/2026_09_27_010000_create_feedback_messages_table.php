<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buzón de sugerencias: estudiantes (desde 6.°) y acudientes escriben
     * sugerencias, quejas, observaciones o reconocimientos sobre una clase o
     * sobre el colegio. Solo lo leen los admins autorizados; el docente
     * mencionado no lo ve.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_review_feedback')->default(false)->after('role');
        });

        Schema::create('feedback_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quien escribe
            $table->string('author_role', 20); // student | guardian (se conserva aunque cambie la cuenta)
            $table->foreignId('student_id')->constrained()->cascadeOnDelete(); // el estudiante al que se refiere
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete(); // su grupo al escribir
            $table->string('type', 20); // suggestion | complaint | observation | recognition
            // Clase mencionada (vacía = el colegio en general). Materia y docente se guardan
            // aparte para conservar el historial aunque cambien las asignaciones.
            $table->foreignId('teacher_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('received');
            $table->text('reply')->nullable(); // respuesta visible para quien escribió
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('reply_seen_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Los admins que ya existen (la cuenta inicial) quedan autorizados; desde el
        // panel deciden a quién más se lo dan.
        DB::table('users')->where('role', Role::Admin->value)->update(['can_review_feedback' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_messages');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('can_review_feedback'));
    }
};
