<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 3, capa 3: correos a las familias.
     * - sent_notifications es la bandeja de salida: cada aviso queda aquí una
     *   sola vez (tipo + acudiente + sobre qué) y un comando programado lo
     *   envía respetando el tope diario del correo (Gmail ~500 al día).
     * - events.send_reminder: el admin decide qué eventos se recuerdan.
     * - guardians.email_notifications: el acudiente puede dejar de recibirlos.
     */
    public function up(): void
    {
        Schema::create('sent_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 60);
            $table->unsignedBigInteger('subject_id');
            // Para el aviso de seguridad al correo anterior (cambio de correo)
            $table->string('email')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'guardian_id', 'subject_type', 'subject_id'], 'sent_notifications_unique');
            $table->index(['sent_at', 'id']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('send_reminder')->default(false)->after('level');
        });

        Schema::table('guardians', function (Blueprint $table) {
            $table->boolean('email_notifications')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('guardians', fn (Blueprint $table) => $table->dropColumn('email_notifications'));
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('send_reminder'));
        Schema::dropIfExists('sent_notifications');
    }
};
