<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apóyanos: cuentas para donar, donantes y aliados, testimonios de
     * voluntarios y solicitudes de voluntariado.
     */
    public function up(): void
    {
        Schema::create('donation_accounts', function (Blueprint $table) {
            $table->id();
            // "Bancolombia · Cuenta de ahorros", "Nequi"…
            $table->string('label', 120);
            $table->string('number', 60);
            $table->string('holder', 120)->nullable();
            // NIT o documento del titular
            $table->string('holder_document', 40)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            // "Empresa aliada", "Iglesia", "Familia donante"…
            $table->string('description', 160)->nullable();
            $table->string('website')->nullable();
            // Cuándo se registró que el donante autorizó publicar su nombre
            $table->timestamp('consent_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('quote', 400);
            $table->string('author', 80);
            $table->string('role', 80)->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('volunteer_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->boolean('phone_has_whatsapp')->default(false);
            $table->string('email', 120)->nullable();
            $table->json('areas');
            $table->string('availability', 30);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();
            // Prueba de la autorización (Ley 1581 de 2012)
            $table->timestamp('privacy_accepted_at');
            $table->string('privacy_policy_version', 40);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_applications');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('donors');
        Schema::dropIfExists('donation_accounts');
    }
};
