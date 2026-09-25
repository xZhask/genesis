<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2: el acudiente pide cambiar su teléfono o su correo desde el
     * portal. El dato no se sobrescribe hasta que el admin lo aprueba. Una
     * fila por dato, para poder aprobar uno y rechazar el otro.
     */
    public function up(): void
    {
        Schema::create('contact_update_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->string('field', 20); // phone | email
            $table->string('old_value')->nullable();
            $table->string('new_value');
            $table->string('status', 20)->default('pending');
            $table->string('note', 300)->nullable(); // motivo del rechazo, visible para el acudiente
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['guardian_id', 'field', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_update_requests');
    }
};
