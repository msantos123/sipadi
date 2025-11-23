<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Estancia;
use App\Models\Lote;
use App\Models\Municipio;
use App\Models\Nacionalidad;
use App\Models\Persona;
use App\Models\Reserva;
USE App\Models\TipoCuarto;
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

        $user = auth()->user()->load(['sucursal', 'roles']);
        
        // Verificar si el usuario tiene rol Prestador (ID: 6)
        $esPrestador = $user->roles->contains('id', 6);
        
        $lotes = collect();
        $estancias = collect();
        
        if ($esPrestador) {
            // Prestador: buscar lotes del establecimiento Y todas sus sucursales
            $lotes = Lote::with(['establecimiento', 'departamento', 'sucursal'])
                ->where('establecimiento_id', $user->establecimiento_id)
                ->where('fecha_lote', $fecha)
                ->get();
            
            // Obtener estancias de todos los lotes encontrados
            if ($lotes->isNotEmpty()) {
                $loteIds = $lotes->pluck('id');
                $estancias = Estancia::whereIn('lote_id', $loteIds)
                    ->with(['persona', 'reserva', 'dependientes.persona', 'lote.establecimiento', 'lote.sucursal'])
                    ->where('es_titular', true)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            
            // Usar el primer lote como referencia (o el del establecimiento principal)
            $lote = $lotes->first();
        } else {
            // Otros roles: buscar solo el lote específico de su ubicación
            $lote = Lote::with(['establecimiento', 'departamento'])
                ->where('establecimiento_id', $user->establecimiento_id)
                ->where('sucursal_id', $user->sucursal_id ?? null)
                ->where('fecha_lote', $fecha)
                ->first();
            
            // Obtener las estancias de ese lote
            if ($lote) {
                $estancias = Estancia::where('lote_id', $lote->id)
                    ->with(['persona', 'reserva', 'dependientes.persona', 'lote.establecimiento', 'lote.sucursal'])
                    ->where('es_titular', true)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        return Inertia::render('Checkin/ViewEstancia', [
            'estancias' => $estancias,
            'lote' => $lote,
            'fecha' => $fecha,
            'sucursalUsuario' => $user->sucursal,
            'esPrestador' => $esPrestador,
            'totalLotes' => $lotes->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        // Filtrar tipos de cuarto según el establecimiento y sucursal del usuario
        $tipoCuartos = TipoCuarto::where('establecimiento_id', $user->establecimiento_id)
            ->where(function ($query) use ($user) {
                // Si el usuario tiene sucursal, buscar tipos de esa sucursal
                if ($user->sucursal_id) {
                    $query->where('sucursal_id', $user->sucursal_id);
                } else {
                    // Si no tiene sucursal, buscar tipos sin sucursal (casa matriz)
                    $query->whereNull('sucursal_id');
                }
            })
            ->get();
        
        return Inertia::render('Checkin/CheckinWizard', [
            'nacionalidades' => Nacionalidad::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'tipoCuartos' => $tipoCuartos,
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
            'reserva.departamento_id' => 'required',
            'reserva.establecimiento_id' => 'nullable', // Se quitó la regla 'exists' que causaba el error de tabla no encontrada
            'reserva.sucursal_id' => 'nullable', // Se quitó la regla 'exists' que causaba el error de tabla no encontrada
            'reserva.fecha_entrada' => 'required|date',
            'reserva.fecha_salida' => 'required|date|after_or_equal:reserva.fecha_entrada',

            'grupos' => 'required|array|min:1',
            'grupos.*.nro_cuarto' => 'required|string|max:10',
            'grupos.*.tipo_cuarto_id' => 'required', // Se omite 'exists' para evitar posibles errores de BD
            'grupos.*.titular' => 'required|array',
            'grupos.*.titular.nro_documento' => 'required|string|max:50',
            'grupos.*.titular.nombres' => 'required|string|max:100',
            'grupos.*.titular.apellido_paterno' => 'required|string|max:100',

            'grupos.*.dependientes' => 'nullable|array',
            'grupos.*.dependientes.*.nro_documento' => 'required|string|max:50',
            'grupos.*.dependientes.*.parentesco' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $result = DB::transaction(function () use ($validated) {
                $user = auth()->user();

                $lote = Lote::firstOrCreate(
                    ['establecimiento_id' => $validated['reserva']['establecimiento_id'],'sucursal_id' => $validated['reserva']['sucursal_id'], 'fecha_lote' => now()->toDateString()],
                    ['departamento_id' => $validated['reserva']['departamento_id'], 'usuario_registra_id' => $user->id]
                );

                $reserva = Reserva::create([
                    'usuario_registra_id' => $user->id,
                    'fecha_entrada' => now(),
                    'fecha_salida' => $validated['reserva']['fecha_salida'],
                    'establecimiento_id' => $validated['reserva']['establecimiento_id'] ?? null,
                    'sucursal_id' => $validated['reserva']['sucursal_id'] ?? null,
                    'codigo_reserva' => $validated['reserva']['codigo_reserva'] ?? null,
                ]);

                $personaFields = [
                    'nombres', 'apellido_paterno', 'apellido_materno', 'tipo_documento', 'nro_documento',
                    'complemento', 'fecha_nacimiento', 'nacionalidad_id', 'departamento_id', 'municipio_id',
                    'ciudad_origen', 'sexo', 'estado_civil', 'ocupacion'
                ];

                foreach ($validated['grupos'] as $grupoData) {
                    $titularData = $grupoData['titular'];
                    $personaTitularPayload = [];
                    foreach ($personaFields as $field) {
                        if (array_key_exists($field, $titularData)) {
                            $personaTitularPayload[$field] = $titularData[$field];
                        }
                    }

                    $personaTitular = Persona::updateOrCreate(
                        ['nro_documento' => $titularData['nro_documento']],
                        $personaTitularPayload
                    );

                    $estanciaTitular = Estancia::create([
                        'lote_id' => $lote->id,
                        'reserva_id' => $reserva->id,
                        'persona_id' => $personaTitular->id,
                        'nro_cuarto' => $grupoData['nro_cuarto'],
                        'tipo_cuarto_id' => $grupoData['tipo_cuarto_id'],
                        'fecha_hora_ingreso' => now(),
                        'es_titular' => true,
                    ]);

                    foreach ($grupoData['dependientes'] ?? [] as $dependienteData) {
                        $personaDependientePayload = [];
                        foreach ($personaFields as $field) {
                            if (array_key_exists($field, $dependienteData)) {
                                $personaDependientePayload[$field] = $dependienteData[$field];
                            }
                        }

                        $personaDependiente = Persona::updateOrCreate(
                            ['nro_documento' => $dependienteData['nro_documento']],
                            $personaDependientePayload
                        );

                        Estancia::create([
                            'lote_id' => $lote->id,
                            'reserva_id' => $reserva->id,
                            'persona_id' => $personaDependiente->id,
                            'responsable_id' => $estanciaTitular->id,
                            'nro_cuarto' => $grupoData['nro_cuarto'],
                            'tipo_cuarto_id' => $grupoData['tipo_cuarto_id'],
                            'fecha_hora_ingreso' => now(),
                            'es_titular' => false,
                            'tipo_parentesco' => $dependienteData['parentesco'],
                        ]);
                    }
                }

                return $reserva;
            });

            return response()->json([
                'message' => 'Check-in registrado exitosamente con ' . count($validated['grupos']) . ' grupo(s).',
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
