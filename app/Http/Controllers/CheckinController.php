<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Estancia;
use App\Models\Lote;
use App\Models\Municipio;
use App\Models\Nacionalidad;
use App\Models\Persona;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CheckinController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $fecha = $request->input('fecha', now()->toDateString());

        // Encontrar el lote para el usuario y fecha seleccionada
        $lote = Lote::where('usuario_registra_id', auth()->id())
                    ->where('fecha_lote', $fecha)
                    ->first();

        // Obtener las estancias de ese lote
        $estancias = [];
        if ($lote) {
            $estancias = Estancia::where('lote_id', $lote->id)
                ->with(['persona', 'reserva', 'dependientes.persona'])
                ->where('es_titular', true)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Inertia::render('Checkin/ViewEstancia', [
            'estancias' => $estancias,
            'lote' => $lote,
            'fecha' => $fecha, // Pasamos la fecha a la vista para el filtro
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Checkin/CheckinWizard', [
            'nacionalidades' => Nacionalidad::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validación de Datos
        $validator = Validator::make($request->all(), [
            'reserva.codigo_reserva' => 'nullable|string|max:100',
            'reserva.establecimiento_id' => 'nullable',//|exists:establecimientos,id',
            'reserva.fecha_entrada' => 'required|date',
            'reserva.fecha_salida' => 'required|date|after_or_equal:reserva.fecha_entrada',
            'reserva.nro_cuarto' => 'required|string|max:10',
            'titular' => 'required|array',
            'titular.nro_documento' => 'required|string|max:50',
            'titular.nombres' => 'required|string|max:100',
            'titular.apellido_paterno' => 'required|string|max:100',
            'dependientes' => 'nullable|array',
            'dependientes.*.nro_documento' => 'required_with:dependientes|string|max:50',
            'dependientes.*.parentesco' => 'required_with:dependientes|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $result = DB::transaction(function () use ($validated) {
                // 2. Obtener o crear el Lote para el día y usuario actual
                $lote = Lote::firstOrCreate(
                    [
                        'usuario_registra_id' => auth()->id(),
                        'fecha_lote' => now()->toDateString(),
                    ],
                    [
                        // 'estado' por defecto es 'PENDIENTE_DE_ENVIO' según la migración
                    ]
                );

                // 3. Crear la Reserva
                $reserva = Reserva::create([
                    'usuario_registra_id' => auth()->id(),
                    'fecha_entrada' => $validated['reserva']['fecha_entrada'],
                    'fecha_salida' => $validated['reserva']['fecha_salida'],
                    'establecimiento_id' => $validated['reserva']['establecimiento_id'] ?? null,
                    'codigo_reserva' => $validated['reserva']['codigo_reserva'] ?? null,
                ]);

                // 4. Gestionar Persona Titular
                $titularData = $validated['titular'];
                $personaTitular = Persona::updateOrCreate(
                    ['nro_documento' => $titularData['nro_documento']],
                    $titularData
                );

                // 5. Crear Estancia del Titular
                $estanciaTitular = Estancia::create([
                    'lote_id' => $lote->id,
                    'reserva_id' => $reserva->id,
                    'persona_id' => $personaTitular->id,
                    'nro_cuarto' => $validated['reserva']['nro_cuarto'],
                    'fecha_hora_ingreso' => now(),
                    'es_titular' => true,
                    'tipo_parentesco' => null,
                    'responsable_id' => null,
                ]);

                // 6. Gestionar Acompañantes
                foreach ($validated['dependientes'] ?? [] as $dependienteData) {
                    $personaDependiente = Persona::updateOrCreate(
                        ['nro_documento' => $dependienteData['nro_documento']],
                        $dependienteData
                    );

                    Estancia::create([
                        'lote_id' => $lote->id,
                        'reserva_id' => $reserva->id,
                        'persona_id' => $personaDependiente->id,
                        'responsable_id' => $estanciaTitular->id,
                        'nro_cuarto' => $validated['reserva']['nro_cuarto'],
                        'fecha_hora_ingreso' => now(),
                        'es_titular' => false,
                        'tipo_parentesco' => $dependienteData['parentesco'],
                    ]);
                }

                return $reserva;
            });

            return response()->json([
                'message' => 'Check-in registrado exitosamente.',
                'reserva_id' => $result->id,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al registrar el check-in.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
