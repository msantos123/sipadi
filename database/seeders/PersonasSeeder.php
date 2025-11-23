<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PersonasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $personas = [];

        $nombres = [
            'JUAN', 'MARÍA', 'CARLOS', 'ANA', 'LUIS', 'LAURA', 'PEDRO', 'ELENA',
            'MIGUEL', 'SOFIA', 'JORGE', 'ISABEL', 'FERNANDO', 'CARMEN', 'RICARDO',
            'PATRICIA', 'ROBERTO', 'LUCIA', 'DANIEL', 'ROSA', 'JAVIER', 'MARTA',
            'ALEJANDRO', 'TERESA', 'FRANCISCO', 'CLAUDIA', 'ANTONIO', 'GABRIELA',
            'DAVID', 'ADRIANA', 'JOSÉ', 'VERÓNICA', 'MANUEL', 'DIANA', 'RAÚL',
            'NATALIA', 'SERGIO', 'OLGA', 'ANDRÉS', 'SILVIA', 'EDUARDO', 'MONICA',
            'VICTOR', 'RAQUEL', 'ALBERTO', 'BEATRIZ', 'PABLO', 'EVA', 'RODRIGO', 'INÉS'
        ];

        $apellidosPaterno = [
            'GARCÍA', 'RODRÍGUEZ', 'GONZÁLEZ', 'FERNÁNDEZ', 'LÓPEZ', 'MARTÍNEZ',
            'SÁNCHEZ', 'PÉREZ', 'GÓMEZ', 'MARTÍN', 'JIMÉNEZ', 'RUIZ', 'HERNÁNDEZ',
            'DÍAZ', 'MORENO', 'ÁLVAREZ', 'ROMERO', 'ALONSO', 'GUTIÉRREZ', 'NAVARRO',
            'TORRES', 'DOMÍNGUEZ', 'VÁZQUEZ', 'RAMOS', 'GIL', 'RAMÍREZ', 'SERRANO',
            'BLANCO', 'MOLINA', 'MORALES', 'ORTEGA', 'DELGADO', 'CASTRO', 'ORTIZ',
            'RUBIO', 'MARÍN', 'SANZ', 'IGLESIAS', 'NUÑEZ', 'MEDINA', 'GARRIDO'
        ];

        $apellidosMaterno = [
            'FLORES', 'VARGAS', 'ROJAS', 'CORTEZ', 'PAREDES', 'QUIROGA', 'MENDEZ',
            'AGUILAR', 'REYES', 'CRUZ', 'PONCE', 'RIVERA', 'MIRANDA', 'BRAVO',
            'CAMPOS', 'CARDENAS', 'SALAZAR', 'PEÑA', 'CABRERA', 'ARAGON', 'MENDOZA',
            'SUAREZ', 'ZAMBRANA', 'VELASCO', 'ESCOBAR', 'FRANCO', 'IBAÑEZ', 'PACHECO',
            'GALINDO', 'BENITEZ', 'ACOSTA', 'ARCE', 'BARRIOS', 'CALDERON', 'CORIA'
        ];

        $tiposDocumento = ['ci', 'pasaporte'];
        $sexos = ['M', 'F','O'];
        $estadosCiviles = ['soltero', 'casado', 'divorciado', 'viudo', 'unión_libre'];
        $ocupaciones = [
            'INGENIERO', 'MÉDICO', 'ABOGADO', 'ARQUITECTO', 'CONTADOR', 'ENFERMERO',
            'DOCENTE', 'ADMINISTRADOR', 'COMERCIANTE', 'ESTUDIANTE', 'TÉCNICO',
            'AGRICULTOR', 'MECÁNICO', 'ELECTRICISTA', 'PROGRAMADOR', 'DISEÑADOR',
            'PERIODISTA', 'PSICÓLOGO', 'CHEF', 'ANALISTA', 'CONSULTOR'
        ];

        $ciudadesExtranjeras = [
            'BUENOS AIRES', 'SÃO PAULO', 'LIMA', 'SANTIAGO', 'BOGOTÁ', 'QUITO',
            'CARACAS', 'MONTEVIDEO', 'ASUNCIÓN', 'CIUDAD DE MÉXICO', 'MADRID',
            'ROMA', 'PARÍS', 'BERLÍN', 'LONDRES', 'NUEVA YORK', 'MIAMI', 'TORONTO'
        ];

        for ($i = 0; $i < 50; $i++) {
            $nacionalidadId = $this->getNacionalidadId();
            $esBoliviano = ($nacionalidadId === 1); // Asumiendo que 1 es Bolivia

            $personas[] = [
                'nombres' => $nombres[$i],
                'apellido_paterno' => $apellidosPaterno[array_rand($apellidosPaterno)],
                'apellido_materno' => rand(0, 1) ? $apellidosMaterno[array_rand($apellidosMaterno)] : null,
                'tipo_documento' => $tiposDocumento[array_rand($tiposDocumento)],
                'nro_documento' => $this->generarNumeroDocumento(),
                'complemento' => rand(0, 1) ? ['A', 'B', null][array_rand([0, 1, 2])] : null,
                'fecha_nacimiento' => $this->generarFechaNacimiento(),
                'nacionalidad_id' => $nacionalidadId,
                'departamento_id' => null, // Como solicitaste
                'municipio_id' => null, // Como solicitaste
                'ciudad_origen' => !$esBoliviano ? $ciudadesExtranjeras[array_rand($ciudadesExtranjeras)] : null,
                'sexo' => $sexos[array_rand($sexos)],
                'estado_civil' => $estadosCiviles[array_rand($estadosCiviles)],
                'ocupacion' => $ocupaciones[array_rand($ocupaciones)],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        // Insertar en lotes para mejor performance
        foreach (array_chunk($personas, 25) as $chunk) {
            DB::table('personas')->insert($chunk);
        }
    }

    /**
     * Genera un ID de nacionalidad aleatorio del 1 al 201, excluyendo el 24
     */
    private function getNacionalidadId(): int
    {
        do {
            $id = rand(1, 201);
        } while ($id == 24); // Excluir el ID 24

        return $id;
    }

    /**
     * Genera un número de documento aleatorio
     */
    private function generarNumeroDocumento(): string
    {
        return (string) rand(1000000, 9999999);
    }

    /**
     * Genera una fecha de nacimiento aleatoria entre 18 y 80 años atrás
     */
    private function generarFechaNacimiento(): string
    {
        $fecha = Carbon::now()
            ->subYears(rand(18, 80))
            ->subMonths(rand(0, 11))
            ->subDays(rand(0, 30));

        return $fecha->format('Y-m-d');
    }
}
