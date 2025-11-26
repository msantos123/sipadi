<?php

namespace App\Http\Controllers;

use App\Models\Estancia;
use App\Models\Nacionalidad;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BusquedaController extends Controller
{
    /**
     * Muestra la página de búsqueda avanzada
     */
    public function index()
    {
        $nacionalidades = Nacionalidad::orderBy('gentilicio')->get(['id', 'gentilicio']);
        $departamentos = Departamento::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Busqueda/Index', [
            'nacionalidades' => $nacionalidades,
            'departamentos' => $departamentos,
        ]);
    }

    /**
     * Realiza la búsqueda con los filtros aplicados
     */
    public function search(Request $request)
    {
        $query = Estancia::query()
            ->join('personas', 'estancias.persona_id', '=', 'personas.id')
            ->join('reservas', 'estancias.reserva_id', '=', 'reservas.id')
            ->leftJoin('nacionalidades', 'personas.nacionalidad_id', '=', 'nacionalidades.id')
            ->leftJoin('departamentos', 'personas.departamento_id', '=', 'departamentos.id');

        // Filtro por nacionalidad
        $query->when($request->nacionalidad_id, function ($q) use ($request) {
            $q->where('personas.nacionalidad_id', $request->nacionalidad_id);
        });

        // Filtro por nombres y apellidos
        $query->when($request->nombre, function ($q) use ($request) {
            $nombre = $request->nombre;
            $q->where(function ($subQuery) use ($nombre) {
                $subQuery->where('personas.nombres', 'like', "%{$nombre}%")
                    ->orWhere('personas.apellido_paterno', 'like', "%{$nombre}%")
                    ->orWhere('personas.apellido_materno', 'like', "%{$nombre}%")
                    ->orWhereRaw("CONCAT(personas.nombres, ' ', personas.apellido_paterno, ' ', personas.apellido_materno) like ?", ["%{$nombre}%"]);
            });
        });

        // Filtro por CI/Pasaporte
        $query->when($request->documento, function ($q) use ($request) {
            $q->where('personas.nro_documento', 'like', "%{$request->documento}%");
        });

        // Filtro por rango de edad
        if ($request->edad_min || $request->edad_max) {
            $query->whereNotNull('personas.fecha_nacimiento');
            
            if ($request->edad_min) {
                $fechaMax = now()->subYears($request->edad_min)->format('Y-m-d');
                $query->where('personas.fecha_nacimiento', '<=', $fechaMax);
            }
            
            if ($request->edad_max) {
                $fechaMin = now()->subYears($request->edad_max + 1)->addDay()->format('Y-m-d');
                $query->where('personas.fecha_nacimiento', '>=', $fechaMin);
            }
        }

        // Filtro por rango de fechas de estancia
        $query->when($request->fecha_desde, function ($q) use ($request) {
            $q->where('estancias.fecha_hora_ingreso', '>=', $request->fecha_desde);
        });

        $query->when($request->fecha_hasta, function ($q) use ($request) {
            $q->where('estancias.fecha_hora_ingreso', '<=', $request->fecha_hasta . ' 23:59:59');
        });

        // Seleccionar las columnas necesarias
        $resultados = $query->select([
            'estancias.id',
            'personas.nombres',
            'personas.apellido_paterno',
            'personas.apellido_materno',
            'personas.tipo_documento',
            'personas.nro_documento',
            'personas.complemento',
            'personas.nacionalidad_id',
            'nacionalidades.gentilicio as nacionalidad',
            'personas.departamento_id',
            'personas.ciudad_origen',
            'personas.sexo',
            'personas.fecha_nacimiento',
            'estancias.fecha_hora_ingreso',
            'estancias.fecha_hora_salida_efectiva',
            'reservas.establecimiento_id',
            'reservas.sucursal_id',
        ])
        ->orderBy('estancias.fecha_hora_ingreso', 'desc')
        ->paginate(15)
        ->through(function ($estancia) {
            // Obtener información del establecimiento desde Sidetur
            $establecimiento = null;
            $departamentoEstablecimiento = null;
            
            if ($estancia->establecimiento_id) {
                $establecimiento = DB::connection('mysql_sidetur')
                    ->table('establecimiento')
                    ->where('id_establecimiento', $estancia->establecimiento_id)
                    ->first(['razon_social', 'id_departamento', 'municipio']);
                
                // Obtener departamento del establecimiento
                if ($establecimiento) {
                    // Primero intentar con id_departamento del establecimiento
                    if ($establecimiento->id_departamento) {
                        $departamentoEstablecimiento = Departamento::find($establecimiento->id_departamento)?->nombre;
                    }
                    
                    // Si no hay id_departamento, intentar con la sucursal
                    if (!$departamentoEstablecimiento && $estancia->sucursal_id) {
                        $sucursal = DB::connection('mysql_sidetur')
                            ->table('sucursal')
                            ->where('id_sucursal', $estancia->sucursal_id)
                            ->first(['id_departamento', 'ciudad']);
                        
                        if ($sucursal) {
                            if ($sucursal->id_departamento) {
                                $departamentoEstablecimiento = Departamento::find($sucursal->id_departamento)?->nombre;
                            } elseif ($sucursal->ciudad) {
                                // Usar ciudad como fallback
                                $departamentoEstablecimiento = $sucursal->ciudad;
                            }
                        }
                    }
                    
                    // Si aún no hay departamento, usar municipio del establecimiento
                    if (!$departamentoEstablecimiento && $establecimiento->municipio) {
                        $departamentoEstablecimiento = $establecimiento->municipio;
                    }
                }
            }

            // Calcular edad
            $edad = null;
            if ($estancia->fecha_nacimiento) {
                $edad = \Carbon\Carbon::parse($estancia->fecha_nacimiento)->age;
            }

            // Determinar procedencia: si es boliviano (nacionalidad_id = 24), mostrar departamento
            $procedencia = 'N/A';
            if ($estancia->nacionalidad_id == 24) {
                // Es boliviano, mostrar departamento
                if ($estancia->departamento_id) {
                    $departamento = Departamento::find($estancia->departamento_id);
                    $procedencia = $departamento?->nombre ?? 'N/A';
                }
            } else {
                // No es boliviano, mostrar ciudad de origen
                $procedencia = $estancia->ciudad_origen ?? 'N/A';
            }

            return [
                'id' => $estancia->id,
                'nombre_completo' => trim("{$estancia->nombres} {$estancia->apellido_paterno} {$estancia->apellido_materno}"),
                'documento' => trim("{$estancia->tipo_documento} {$estancia->nro_documento}" . ($estancia->complemento ? "-{$estancia->complemento}" : "")),
                'nacionalidad' => $estancia->nacionalidad ?? 'N/A',
                'procedencia' => $procedencia,
                'sexo' => $estancia->sexo ?? 'N/A',
                'edad' => $edad,
                'fecha_ingreso' => $estancia->fecha_hora_ingreso ? \Carbon\Carbon::parse($estancia->fecha_hora_ingreso)->format('d/m/Y H:i') : 'N/A',
                'fecha_salida' => $estancia->fecha_hora_salida_efectiva ? \Carbon\Carbon::parse($estancia->fecha_hora_salida_efectiva)->format('d/m/Y H:i') : 'N/A',
                'establecimiento' => $establecimiento?->razon_social ?? 'N/A',
                'departamento_establecimiento' => $departamentoEstablecimiento ?? 'N/A',
            ];
        });

        return Inertia::render('Busqueda/Index', [
            'nacionalidades' => Nacionalidad::orderBy('gentilicio')->get(['id', 'gentilicio']),
            'departamentos' => Departamento::orderBy('nombre')->get(['id', 'nombre']),
            'resultados' => $resultados,
            'filtros' => $request->only(['nacionalidad_id', 'nombre', 'documento', 'edad_min', 'edad_max', 'rango_edad', 'fecha_desde', 'fecha_hasta']),
        ]);
    }
}

