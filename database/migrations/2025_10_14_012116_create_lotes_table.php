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
            $table->date('fecha_lote');
            $table->foreignId('usuario_registra_id')->constrained('users');
            $table->enum('estado_lote', ['PENDIENTE_DE_ENVIO', 'EN_REVISION_GAD','EN_REVISION_VMT','COMPLETADO'])->default('PENDIENTE_DE_ENVIO');
            $table->timestamp('fecha_envio_operador')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_envio_gad')->nullable();
            $table->timestamps();
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
