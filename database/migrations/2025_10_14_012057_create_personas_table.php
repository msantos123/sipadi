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
       Schema::create('personas', function (Blueprint $table) {
        $table->id();
        $table->string('nombres', 100);
        $table->string('apellido_paterno', 100);
        $table->string('apellido_materno', 100)->nullable();
        $table->string('tipo_documento', 20);
        $table->string('nro_documento', 50);
        $table->string('complemento', 10)->nullable();
        $table->date('fecha_nacimiento');

        // Para nacionalidad (relación con tabla de nacionalidades)
        $table->foreignId('nacionalidad_id')->nullable()->constrained('nacionalidades');

        // Para bolivianos: departamento y municipio
        $table->foreignId('departamento_id')->nullable()->constrained('departamentos');
        $table->foreignId('municipio_id')->nullable()->constrained('municipios');

        // Para extranjeros: ciudad de origen (texto libre)
        $table->string('ciudad_origen', 100)->nullable();

        $table->string('sexo', 15)->nullable();
        $table->string('estado_civil', 50)->nullable();
        $table->string('ocupacion', 100)->nullable();
        $table->timestamps();

        $table->unique(['nro_documento', 'complemento']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
