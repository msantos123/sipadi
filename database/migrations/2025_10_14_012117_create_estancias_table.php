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

            // En la tabla 'estancias'
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->onDelete('cascade'); // Si se borra un lote, se borran sus estancias

            //con la tabla tipo de cuarto
            $table->foreignId('tipo_cuarto_id')->constrained('tipo_cuartos')->onDelete('restrict'); // Línea añadida
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estancias');
    }
};
