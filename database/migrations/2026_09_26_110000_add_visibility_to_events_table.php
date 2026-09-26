<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eventos solo para familias: un calendario público dice dónde y cuándo
     * estará un grupo de niños (salidas pedagógicas, actividades fuera del
     * colegio). Estos eventos se ven en el calendario del portal, opcionalmente
     * solo en algunos grados. Los eventos existentes siguen siendo públicos.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('level');
            // Grados destinatarios ("5.°"…); null = todas las familias del nivel
            $table->json('audience')->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'audience']);
        });
    }
};
