<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de pre-inscripción enviadas desde la web pública.
     * Solo se piden los datos necesarios para contactar a la familia (Ley 1581).
     */
    public function up(): void
    {
        Schema::create('admission_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();
            $table->unsignedSmallInteger('school_year');
            // Texto por ahora ("Transición", "6.°"); se enlaza a grades en la fase 2.
            $table->string('grade', 30);

            $table->string('student_first_names', 80);
            $table->string('student_last_names', 80);
            $table->date('student_birth_date');
            $table->string('current_school', 120)->nullable();

            $table->string('guardian_name', 120);
            $table->string('guardian_relationship', 20);
            $table->string('guardian_phone', 20);
            $table->boolean('phone_has_whatsapp')->default(false);
            $table->string('guardian_email', 120)->nullable();

            $table->text('comments')->nullable();
            $table->string('status', 30)->default('received')->index();

            // Prueba de la autorización de tratamiento de datos
            $table->timestamp('privacy_accepted_at');
            $table->string('privacy_policy_version', 30);
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index(['school_year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_requests');
    }
};
