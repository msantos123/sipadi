<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->onDelete('cascade');
            $table->foreignId('persona_id')->constrained('personas')->onDelete('restrict');
            $table->foreignId('responsable_id')->nullable()->constrained('estancias')->onDelete('set null');

            // Ciclo de vida
            $table->string('nro_cuarto', 10);
            $table->dateTime('fecha_hora_ingreso');
            $table->dateTime('fecha_hora_salida_efectiva')->nullable();
            $table->enum('estado_estancia', ['ACTIVA', 'FINALIZADA', 'CANCELADA'])->default('ACTIVA');

            // Datos del huésped
            $table->boolean('es_titular')->default(false);
            $table->string('tipo_parentesco', 50)->nullable();

            // Nivel 1: Aprobación GAD
            $table->enum('estado_aprobacion_gad', ['PENDIENTE', 'APROBADO', 'RECHAZADO'])->default('PENDIENTE');
            $table->foreignId('gad_usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('gad_fecha_aprobacion')->nullable();
            $table->text('gad_observaciones')->nullable();

            // Nivel 2: Aprobación VMT
            $table->enum('estado_aprobacion_vmt', ['EN_ESPERA', 'PENDIENTE', 'APROBADO', 'RECHAZADO'])->default('EN_ESPERA');
            $table->foreignId('vmt_usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('vmt_fecha_aprobacion')->nullable();
            $table->text('vmt_observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estancias');
    }
};
