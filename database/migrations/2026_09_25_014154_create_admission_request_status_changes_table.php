<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de cambios de estado de cada pre-inscripción: quién, cuándo y por qué.
     */
    public function up(): void
    {
        Schema::table('admission_requests', function (Blueprint $table) {
            $table->dateTime('interview_at')->nullable()->after('status');
        });

        Schema::create('admission_request_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->dateTime('interview_at')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_request_status_changes');

        Schema::table('admission_requests', function (Blueprint $table) {
            $table->dropColumn('interview_at');
        });
    }
};
