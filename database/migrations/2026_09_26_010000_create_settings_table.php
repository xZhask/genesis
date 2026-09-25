<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes que el colegio cambia desde el admin (costos, requisitos,
     * horarios…). Cada clave reemplaza el valor de config/school.php.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            // Ruta dentro de config('school'), p. ej. "admissions.costs"
            $table->string('key', 80)->primary();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
