<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            // Foto pequeña y opcional para el muro de agradecimiento
            $table->string('photo_path')->nullable()->after('website');
            // La autorización de consent_at cubre el nombre; la foto lleva la suya
            $table->timestamp('photo_consent_at')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_consent_at']);
        });
    }
};
