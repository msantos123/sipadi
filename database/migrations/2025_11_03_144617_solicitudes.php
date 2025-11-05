<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('persona_buscada_nombre');
            $table->string('persona_buscada_identificacion');
            $table->timestamp('fecha_solicitud');
            $table->string('documento_adjunto_path');
            $table->json('resultado_busqueda')->nullable();
            $table->foreignId('usuario_creador_id')->constrained('users'); // Asume tabla 'users'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
