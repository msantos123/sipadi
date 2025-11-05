<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Estancia;
use App\Models\User;
use App\Models\Establecimiento;
use App\Models\Departamento;
use App\Models\Persona;
use App\Models\TipoCuarto;
use App\Models\Sucursal;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class LotesReservasEstanciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Lotes, Reservas y Estancias...');

        // --- 1. OBTENER DATOS EXISTENTES ---
        $usuarios = User::all();
        $establecimientos = Establecimiento::all();
        $departamentos = Departamento::all();
        $personas = Persona::all();
        $tiposCuarto = TipoCuarto::all();

        // Validar que existan datos suficientes
        if ($usuarios->isEmpty() || $establecimientos->isEmpty() || $departamentos->isEmpty() || $personas->count() < 2 || $tiposCuarto->isEmpty()) {
            $this->command->error('No se encontraron datos suficientes en las tablas de soporte.');
            $this->command->info('Necesitas al menos: 1 usuario, 1 establecimiento, 1 departamento, 2 personas y 1 tipo de cuarto.');
            return;
        }

        $cantidad_a_crear = 10; // <--- Puedes cambiar este número para crear más o menos registros
        $this->command->info("Se crearán {$cantidad_a_crear} lotes/reservas.");

        for ($i = 0; $i < $cantidad_a_crear; $i++) {
            // --- 2. SELECCIONAR DATOS ALEATORIOS ---
            $usuario = $usuarios->random();
            $establecimiento = $establecimientos->random();
            $departamento = $departamentos->random();
            $sucursal = Sucursal::where('id_casa_matriz', $establecimiento->id_establecimiento)->first(); // Puede ser null

            $usuario_aleatorio_id = Arr::random([4, 5, 6, 7]);//solo usuarios prestadoresemp
            // --- 3. CREAR UN LOTE ---
            $lote = Lote::create([
                'fecha_lote' => Carbon::create(2025, 10, 30),
                'estado_lote' => 'PENDIENTE_DE_ENVIO',
                'establecimiento_id' => $establecimiento->id_establecimiento,
                'departamento_id' => $departamento->id,
                'usuario_registra_id' => $usuario_aleatorio_id,
            ]);

            // --- 4. CREAR UNA RESERVA ---
            $fechaEntrada = Carbon::create(2025, 10, rand(1, 25));
            $fechaSalida = $fechaEntrada->copy()->addDays(rand(2, 5));
            $establecimiento_aleatorio = Arr::random([111, 112]);
            $reserva = Reserva::create([
                'codigo_reserva' => 'RES-' . uniqid(),
                'establecimiento_id' => $establecimiento_aleatorio,
                'sucursal_id' => $sucursal ? $sucursal->id_sucursal : null,
                'usuario_registra_id' => $usuario->id,
                'fecha_entrada' => Carbon::create(2025, 10, 30),
                'fecha_salida' => $fechaSalida,
            ]);

            // --- 5. CREAR ESTANCIAS (TITULAR Y ACOMPAÑANTES) ---
            $personasDeReserva = $personas->random(rand(1, min(4, $personas->count()))); // Tomar entre 1 y 4 personas aleatorias
            $titular = $personasDeReserva->shift(); // La primera persona es el titular
            $tipoCuarto = $tiposCuarto->random();

            // Crear la estancia para el titular
            $estanciaTitular = Estancia::create([
                'reserva_id' => $reserva->id,
                'persona_id' => $titular->id,
                'responsable_id' => null,
                'nro_cuarto' => $tipoCuarto->nombre . '-' . rand(100, 199),
                'fecha_hora_ingreso' => Carbon::create(2025, 10, 30),
                'estado_estancia' => 'ACTIVA',
                'es_titular' => true,
                'lote_id' => $lote->id,
                'tipo_cuarto_id' => $tipoCuarto->id,
            ]);

            // Crear estancias para los acompañantes
            $tipo_parentesco_aleatorio = Arr::random(['hijo','sobrino','hermano','nieto','apoderado']);
            foreach ($personasDeReserva as $acompanante) {
                Estancia::create([
                    'reserva_id' => $reserva->id,
                    'persona_id' => $acompanante->id,
                    'responsable_id' => $estanciaTitular->id, // El titular es el responsable
                    'nro_cuarto' => $estanciaTitular->nro_cuarto,
                    'fecha_hora_ingreso' => Carbon::create(2025, 10, 30),
                    'estado_estancia' => 'ACTIVA',
                    'es_titular' => false,
                    'tipo_parentesco' => 'OTRO',
                    'lote_id' => $lote->id,
                    'tipo_cuarto_id' => $tipoCuarto->id,
                ]);
            }
            $this->command->info("Lote {$lote->id} y Reserva {$reserva->id} creados con 1 titular y " . $personasDeReserva->count() . " acompañantes.");
        }

        $this->command->info('Seeder de Lotes, Reservas y Estancias finalizado.');
    }
}
