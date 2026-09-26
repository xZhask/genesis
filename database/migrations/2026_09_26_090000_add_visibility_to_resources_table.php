<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Circulares solo para familias: no todas se pueden publicar en la web
     * (salidas pedagógicas, cobros, asuntos de un grupo). Las que son «solo
     * familias» se ven en el portal del acudiente, opcionalmente solo en
     * algunos grados, y su PDF se guarda fuera del disco público.
     * Los recursos existentes siguen siendo públicos.
     */
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('status');
            // Grados destinatarios ("5.°", "Jardín"…); null = todas las familias
            $table->json('audience')->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'audience']);
        });
    }
};
