<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuatro roles fijos (CLAUDE.md): admin, teacher, student, guardian.
     * Se guarda como texto y se valida con el enum App\Enums\Role.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('guardian')->after('email')->index();
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
