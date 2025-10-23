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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();

            // --- Columnas Principales ---
            $table->date('fecha_lote');
            $table->enum('estado_lote', ['PENDIENTE_DE_ENVIO', 'EN_REVISION_GAD', 'EN_REVISION_VMT', 'COMPLETADO'])->default('PENDIENTE_DE_ENVIO');

            // --- Relaciones y Auditoría ---
            // IDs para filtrado y estadísticas
            $table->foreignId('establecimiento_id')->nullable();
            $table->foreignId('departamento_id')->constrained('departamentos');

            // Quién creó el lote
            $table->foreignId('usuario_registra_id')->constrained('users');

            // Quién y cuándo lo envió el operador
            $table->foreignId('operador_envio_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_envio_operador')->nullable();

            // Cuándo lo vio/procesó la GAD
            $table->timestamp('fecha_envio_gad')->nullable();
            // Cuándo lo vio/procesó la vmt
            $table->timestamp('fecha_envio_vmt')->nullable();

            $table->timestamps();

            // --- Índices para Rendimiento ---
            $table->index('fecha_lote');
            $table->index('estado_lote');
            $table->index('establecimiento_id');
            $table->index('departamento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
