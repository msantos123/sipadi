<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HabitacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_cuartos')->insert([
            [
                'nombre'           => 'Simple',
                'nro_habitaciones' => 15, // Ejemplo: 15 habitaciones de este tipo
                'nro_personas'     => 1,  // Capacidad para 1 persona
                // 'precio_base'      => 50.00, // Si incluiste esta columna
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'nombre'           => 'Doble',
                'nro_habitaciones' => 25, // Ejemplo: 25 habitaciones de este tipo
                'nro_personas'     => 2,  // Capacidad para 2 personas
                // 'precio_base'      => 75.50,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'nombre'           => 'Triple',
                'nro_habitaciones' => 10,
                'nro_personas'     => 3,
                // 'precio_base'      => 95.00,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            // Puedes agregar más tipos de cuartos aquí
        ]);
    }
}
