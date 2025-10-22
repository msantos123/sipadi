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
            'Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Laura', 'Pedro', 'Elena',
            'Miguel', 'Sofia', 'Jorge', 'Isabel', 'Fernando', 'Carmen', 'Ricardo',
            'Patricia', 'Roberto', 'Lucia', 'Daniel', 'Rosa', 'Javier', 'Marta',
            'Alejandro', 'Teresa', 'Francisco', 'Claudia', 'Antonio', 'Gabriela',
            'David', 'Adriana', 'José', 'Verónica', 'Manuel', 'Diana', 'Raúl',
            'Natalia', 'Sergio', 'Olga', 'Andrés', 'Silvia', 'Eduardo', 'Monica',
            'Victor', 'Raquel', 'Alberto', 'Beatriz', 'Pablo', 'Eva', 'Rodrigo', 'Inés'
        ];

        $apellidosPaterno = [
            'García', 'Rodríguez', 'González', 'Fernández', 'López', 'Martínez',
            'Sánchez', 'Pérez', 'Gómez', 'Martín', 'Jiménez', 'Ruiz', 'Hernández',
            'Díaz', 'Moreno', 'Álvarez', 'Romero', 'Alonso', 'Gutiérrez', 'Navarro',
            'Torres', 'Domínguez', 'Vázquez', 'Ramos', 'Gil', 'Ramírez', 'Serrano',
            'Blanco', 'Molina', 'Morales', 'Ortega', 'Delgado', 'Castro', 'Ortiz',
            'Rubio', 'Marín', 'Sanz', 'Iglesias', 'Nuñez', 'Medina', 'Garrido'
        ];

        $apellidosMaterno = [
            'Flores', 'Vargas', 'Rojas', 'Cortez', 'Paredes', 'Quiroga', 'Mendez',
            'Aguilar', 'Reyes', 'Cruz', 'Ponce', 'Rivera', 'Miranda', 'Bravo',
            'Campos', 'Cardenas', 'Salazar', 'Peña', 'Cabrera', 'Aragon', 'Mendoza',
            'Suarez', 'Zambrana', 'Velasco', 'Escobar', 'Franco', 'Ibañez', 'Pacheco',
            'Galindo', 'Benitez', 'Acosta', 'Arce', 'Barrios', 'Calderon', 'Coria'
        ];

        $tiposDocumento = ['CI', 'Pasaporte'];
        $sexos = ['Masculino', 'Femenino'];
        $estadosCiviles = ['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a'];
        $ocupaciones = [
            'Ingeniero', 'Médico', 'Abogado', 'Arquitecto', 'Contador', 'Enfermero',
            'Docente', 'Administrador', 'Comerciante', 'Estudiante', 'Técnico',
            'Agricultor', 'Mecánico', 'Electricista', 'Programador', 'Diseñador',
            'Periodista', 'Psicólogo', 'Chef', 'Analista', 'Consultor'
        ];

        $ciudadesExtranjeras = [
            'Buenos Aires', 'São Paulo', 'Lima', 'Santiago', 'Bogotá', 'Quito',
            'Caracas', 'Montevideo', 'Asunción', 'Ciudad de México', 'Madrid',
            'Roma', 'París', 'Berlín', 'Londres', 'Nueva York', 'Miami', 'Toronto'
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
