<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cumpleaños:
     * - users.birth_date: la del personal (la de los estudiantes ya existe).
     * - users.birthday_emails: docentes y estudiantes pueden dejar de recibir la tarjeta.
     * - birthday_greetings: una tarjeta por persona y cumpleaños, aunque el cron se repita.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('document_number');
            $table->boolean('birthday_emails')->default(true)->after('birth_date');
        });

        Schema::create('birthday_greetings', function (Blueprint $table) {
            $table->id();
            $table->string('celebrant_type', 60);
            $table->unsignedBigInteger('celebrant_id');
            $table->date('celebrated_on');
            // Destinatario: la persona o, si no tiene correo, su acudiente
            $table->string('email');
            $table->foreignId('guardian_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['celebrant_type', 'celebrant_id', 'celebrated_on'], 'birthday_greetings_unique');
            $table->index(['sent_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_greetings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['birth_date', 'birthday_emails']));
    }
};
