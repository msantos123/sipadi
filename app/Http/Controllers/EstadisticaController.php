<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\Estancia;
use App\Models\Nacionalidad;
use App\Models\TipoCuarto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class EstadisticaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('id')->toArray();
        
        // Determinar alcance según rol
        // Admin (1) y Nacional (2) - Acceso total
        if (in_array(1, $userRoles) || in_array(2, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::all();
            $establecimientos = Establecimiento::where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::all();
            $alcance = 'nacional';
            
        // Departamental (4) - Solo su departamento
        } elseif (in_array(4, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::where('id', $user->departamento_id)->get();
            $establecimientos = Establecimiento::where('id_departamento', $user->departamento_id)->where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::where('id_departamento', $user->departamento_id)->get();
            $alcance = 'departamental';
            
        // Prestador (6) - Solo su establecimiento y sucursales
        } elseif (in_array(6, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = collect(); // Vacío
            $establecimientos = Establecimiento::where('id_establecimiento', $user->establecimiento_id)->get();
            $sucursales = \App\Models\Sucursal::where('id_casa_matriz', $user->establecimiento_id)->get();
            $alcance = 'establecimiento';
            
        } else {
            // Sin acceso
            abort(403, 'No tienes permiso para acceder a estadísticas');
        }

        return Inertia::render('Estadisticas/Index', [
            'nacionalidades' => $nacionalidades,
            'departamentos' => $departamentos,
            'establecimientos' => $establecimientos,
            'sucursales' => $sucursales,
            'alcance' => $alcance,
            'estadisticas' => null,
            'filters' => [],
        ]);
    }

    public function generar(Request $request)
    {
        Log::info('--- Inicio de generación de estadísticas ---');
        Log::info('Filtros recibidos:', $request->all());

        $user = auth()->user();
        $userRoles = $user->roles->pluck('id')->toArray();

        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'departamento_ids' => 'nullable|array',
            'departamento_ids.*' => 'exists:departamentos,id',
            'establecimiento_ids' => 'nullable|array',
            'establecimiento_ids.*' => 'exists:mysql_sidetur.establecimiento,id_establecimiento',
            'sucursal_ids' => 'nullable|array',
            'sucursal_ids.*' => 'exists:mysql_sidetur.sucursal,id_sucursal',
        ]);

        // Aplicar restricciones según rol ANTES de filtros del usuario
        // Departamental (4) - Forzar solo su departamento
        if (in_array(4, $userRoles)) {
            $request->merge(['departamento_ids' => [$user->departamento_id]]);
        }
        
        // Prestador (6) - Forzar solo su establecimiento
        if (in_array(6, $userRoles)) {
            $request->merge(['establecimiento_ids' => [$user->establecimiento_id]]);
        }

        $fechaInicio = $request->fecha_inicio ? Carbon::parse($request->fecha_inicio)->startOfDay() : Carbon::now()->startOfDay();
        $fechaFin = $request->fecha_fin ? Carbon::parse($request->fecha_fin)->endOfDay() : Carbon::now()->endOfDay();

        // Base query
        $estanciasQuery = Estancia::with('persona', 'tipoCuarto')
            ->whereBetween('fecha_hora_ingreso', [$fechaInicio, $fechaFin]);

        // Filtrar por múltiples departamentos (solo si hay departamentos seleccionados)
        if ($request->filled('departamento_ids') && is_array($request->departamento_ids) && count($request->departamento_ids) > 0) {
            
            $departamentoIds = $request->departamento_ids;

            $establecimientoIds = Establecimiento::whereIn('id_departamento', $departamentoIds)->pluck('id_establecimiento');
            $sucursalIds = \App\Models\Sucursal::whereIn('id_departamento', $departamentoIds)->pluck('id_sucursal');

            if ($establecimientoIds->isNotEmpty() || $sucursalIds->isNotEmpty()) {
                $estanciasQuery->whereHas('tipoCuarto', function ($q) use ($establecimientoIds, $sucursalIds) {
                    $q->where(function ($subQuery) use ($establecimientoIds, $sucursalIds) {
                        if ($establecimientoIds->isNotEmpty()) {
                            $subQuery->whereIn('establecimiento_id', $establecimientoIds);
                        }
                        if ($sucursalIds->isNotEmpty()) {
                            $subQuery->orWhereIn('sucursal_id', $sucursalIds);
                        }
                    });
                });
            }
        }

        // Filtrar por múltiples establecimientos (solo si hay establecimientos seleccionados)
        if ($request->filled('establecimiento_ids') && is_array($request->establecimiento_ids) && count($request->establecimiento_ids) > 0) {
            $establecimientoIds = $request->establecimiento_ids;
                
            $estanciasQuery->whereHas('tipoCuarto', function ($q) use ($establecimientoIds, $request) {
                $q->whereIn('establecimiento_id', $establecimientoIds);
                
                // Si también hay sucursales, filtrar por ellas
                if ($request->filled('sucursal_ids') && is_array($request->sucursal_ids) && count($request->sucursal_ids) > 0) {
                    $sucursalIds = $request->sucursal_ids;
                    $q->whereIn('sucursal_id', $sucursalIds);
                }
            });
        } elseif ($request->filled('sucursal_ids') && is_array($request->sucursal_ids) && count($request->sucursal_ids) > 0) {
            // Solo filtrar por sucursales si no hay establecimientos
            $sucursalIds = $request->sucursal_ids;
                
            $estanciasQuery->whereHas('tipoCuarto', function ($q) use ($sucursalIds) {
                $q->whereIn('sucursal_id', $sucursalIds);
            });
        }

        $estancias = $estanciasQuery->get();
        
        // 1. Total de Llegadas
        $totalLlegadas = $estancias->unique('persona_id')->count();

        // Llegadas por nacionalidad (usando las estancias ya filtradas)
        $llegadasPorNacionalidad = $estancias
            ->unique('persona_id')
            ->groupBy(function($estancia) {
                return $estancia->persona?->nacionalidad?->gentilicio ?? 'Desconocida';
            })
            ->map->count();


        // 2. Total Pernoctaciones
        $totalPernoctaciones = $estancias->sum(function ($estancia) {
            if ($estancia->fecha_hora_salida_efectiva) {
                $ingreso = Carbon::parse($estancia->fecha_hora_ingreso);
                $salida = Carbon::parse($estancia->fecha_hora_salida_efectiva);
                $noches = $ingreso->startOfDay()->diffInDays($salida->startOfDay());
                return $noches > 0 ? $noches : 0;
            }
            return 0;
        });

        // 3. Estadia Media
        $estadiaMedia = $totalLlegadas > 0 ? $totalPernoctaciones / $totalLlegadas : 0;

        // 4. Tasa de Ocupacion
        $habitacionesDisponiblesQuery = TipoCuarto::query();
        
        if ($request->filled('establecimiento_ids')) {
            $establecimientoIds = is_array($request->establecimiento_ids) 
                ? $request->establecimiento_ids 
                : [$request->establecimiento_ids];
            $habitacionesDisponiblesQuery->whereIn('establecimiento_id', $establecimientoIds);
        }
        
        if ($request->filled('sucursal_ids')) {
            $sucursalIds = is_array($request->sucursal_ids) 
                ? $request->sucursal_ids 
                : [$request->sucursal_ids];
            $habitacionesDisponiblesQuery->whereIn('sucursal_id', $sucursalIds);
        }
        
        $totalHabitacionesDisponibles = $habitacionesDisponiblesQuery->sum('nro_habitaciones');

        $habitacionesOcupadas = $estancias->pluck('nro_cuarto')->unique()->count();

        $tasaOcupacion = $totalHabitacionesDisponibles > 0 
            ? ($habitacionesOcupadas / $totalHabitacionesDisponibles) * 100 
            : 0;

        // 5. Distribución por Rangos de Edad
        $personasUnicas = $estancias->unique('persona_id')->pluck('persona');
        
        $distribucionEdad = [
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-60' => 0,
            '60+' => 0,
        ];

        foreach ($personasUnicas as $persona) {
            if ($persona && $persona->fecha_nacimiento) {
                $edad = \Carbon\Carbon::parse($persona->fecha_nacimiento)->age;
                if ($edad >= 18 && $edad <= 25) {
                    $distribucionEdad['18-25']++;
                } elseif ($edad >= 26 && $edad <= 35) {
                    $distribucionEdad['26-35']++;
                } elseif ($edad >= 36 && $edad <= 45) {
                    $distribucionEdad['36-45']++;
                } elseif ($edad >= 46 && $edad <= 60) {
                    $distribucionEdad['46-60']++;
                } elseif ($edad > 60) {
                    $distribucionEdad['60+']++;
                }
            }
        }

        // 6. Distribución por Sexo
        $distribucionSexo = [
            'Masculino' => 0,
            'Femenino' => 0,
            'Otro' => 0,
        ];

        foreach ($personasUnicas as $persona) {
            if ($persona && $persona->sexo) {
                if (strtoupper($persona->sexo) === 'M' || strtoupper($persona->sexo) === 'MASCULINO') {
                    $distribucionSexo['Masculino']++;
                } elseif (strtoupper($persona->sexo) === 'F' || strtoupper($persona->sexo) === 'FEMENINO') {
                    $distribucionSexo['Femenino']++;
                } else {
                    $distribucionSexo['Otro']++;
                }
            }
        }

        // 7. Top 6 Procedencias (Países)
        $procedencias = $personasUnicas
            ->filter(function($persona) {
                return $persona && $persona->nacionalidad;
            })
            ->groupBy(function($persona) {
                return $persona->nacionalidad->pais ?? 'Desconocido';
            })
            ->map->count()
            ->sortDesc()
            ->take(6);

        // 8. Coordenadas de países para mapa mundial
        $coordenadasPaises = [
            'Bolivia' => [-16.5, -68.15],
            'Argentina' => [-38.4161, -63.6167],
            'Brasil' => [-14.235, -51.9253],
            'Chile' => [-35.6751, -71.543],
            'Perú' => [-9.19, -75.0152],
            'Colombia' => [4.5709, -74.2973],
            'Ecuador' => [-1.8312, -78.1834],
            'Venezuela' => [6.4238, -66.5897],
            'Paraguay' => [-23.4425, -58.4438],
            'Uruguay' => [-32.5228, -55.7658],
            'México' => [23.6345, -102.5528],
            'Estados Unidos' => [37.0902, -95.7129],
            'Canadá' => [56.1304, -106.3468],
            'España' => [40.4637, -3.7492],
            'Francia' => [46.2276, 2.2137],
            'Alemania' => [51.1657, 10.4515],
            'Italia' => [41.8719, 12.5674],
            'Reino Unido' => [55.3781, -3.436],
            'China' => [35.8617, 104.1954],
            'Japón' => [36.2048, 138.2529],
            'Corea del Sur' => [35.9078, 127.7669],
            'India' => [20.5937, 78.9629],
            'Rusia' => [61.524, 105.3188],
            'Australia' => [-25.2744, 133.7751],
            'Sudáfrica' => [-30.5595, 22.9375],
            'Egipto' => [26.8206, 30.8025],
            'Marruecos' => [31.7917, -7.0926],
            'Tayikistán' => [38.861, 71.2761],
            'El Salvador' => [13.7942, -88.8965],
            'Togo' => [8.6195, 0.8248],
        ];

        $puntosMapa = [];
        foreach ($personasUnicas as $persona) {
            if ($persona && $persona->nacionalidad && $persona->nacionalidad->pais) {
                $pais = $persona->nacionalidad->pais;
                if (isset($coordenadasPaises[$pais])) {
                    if (!isset($puntosMapa[$pais])) {
                        $puntosMapa[$pais] = [
                            'pais' => $pais,
                            'lat' => $coordenadasPaises[$pais][0],
                            'lng' => $coordenadasPaises[$pais][1],
                            'count' => 0,
                        ];
                    }
                    $puntosMapa[$pais]['count']++;
                }
            }
        }

        $estadisticas = [
            'totalLlegadas' => $totalLlegadas,
            'llegadasPorNacionalidad' => $llegadasPorNacionalidad->toArray(),
            'totalPernoctaciones' => $totalPernoctaciones,
            'estadiaMedia' => round($estadiaMedia, 2),
            'tasaOcupacion' => round($tasaOcupacion, 2),
            'habitacionesOcupadas' => $habitacionesOcupadas,
            'totalHabitacionesDisponibles' => $totalHabitacionesDisponibles,
            'distribucionEdad' => $distribucionEdad,
            'distribucionSexo' => $distribucionSexo,
            'topProcedencias' => $procedencias->toArray(),
            'puntosMapa' => array_values($puntosMapa),
        ];
        Log::info('Array de estadísticas calculado:', $estadisticas);
        Log::info('--- Fin de generación de estadísticas ---');

        // Recargar datos según alcance del usuario
        if (in_array(1, $userRoles) || in_array(2, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::all();
            $establecimientos = Establecimiento::where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::all();
            $alcance = 'nacional';
        } elseif (in_array(4, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::where('id', $user->departamento_id)->get();
            $establecimientos = Establecimiento::where('id_departamento', $user->departamento_id)->where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::where('id_departamento', $user->departamento_id)->get();
            $alcance = 'departamental';
        } else {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = collect();
            $establecimientos = Establecimiento::where('id_establecimiento', $user->establecimiento_id)->get();
            $sucursales = \App\Models\Sucursal::where('id_casa_matriz', $user->establecimiento_id)->get();
            $alcance = 'establecimiento';
        }

        return Inertia::render('Estadisticas/Index', [
            'nacionalidades' => $nacionalidades,
            'departamentos' => $departamentos,
            'establecimientos' => $establecimientos,
            'sucursales' => $sucursales,
            'alcance' => $alcance,
            'estadisticas' => $estadisticas,
            'filters' => $request->only(['fecha_inicio', 'fecha_fin', 'departamento_ids', 'establecimiento_ids', 'sucursal_ids'])
        ]);
    }
}
