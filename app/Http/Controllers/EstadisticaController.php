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
        $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
        $establecimientos = Establecimiento::with('sucursales')->where('id_prestador', 5)->orderBy('razon_social')->get();

        return Inertia::render('Estadisticas/Index', [
            'nacionalidades' => $nacionalidades,
            'establecimientos' => $establecimientos,
            'estadisticas' => null,
            'filters' => [],
        ]);
    }

    public function generar(Request $request)
    {
        Log::info('--- Inicio de generación de estadísticas ---');
        Log::info('Filtros recibidos:', $request->all());

        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'nacionalidad_id' => 'nullable|exists:nacionalidades,id',
            'establecimiento_id' => 'nullable|exists:mysql_sidetur.establecimiento,id_establecimiento',
            'sucursal_id' => 'nullable|exists:mysql_sidetur.sucursal,id_sucursal',
        ]);

        $fechaInicio = $request->fecha_inicio ? Carbon::parse($request->fecha_inicio)->startOfDay() : Carbon::now()->startOfDay();
        $fechaFin = $request->fecha_fin ? Carbon::parse($request->fecha_fin)->endOfDay() : Carbon::now()->endOfDay();

        // Base query
        $estanciasQuery = Estancia::with('persona', 'tipoCuarto')
            ->whereBetween('fecha_hora_ingreso', [$fechaInicio, $fechaFin]);

        // Apply filters
        if ($request->filled('nacionalidad_id')) {
            $estanciasQuery->whereHas('persona', function ($q) use ($request) {
                $q->where('nacionalidad_id', $request->nacionalidad_id);
            });
        }

        if ($request->filled('establecimiento_id')) {
            $estanciasQuery->whereHas('tipoCuarto', function ($q) use ($request) {
                $q->where('establecimiento_id', $request->establecimiento_id);
                if ($request->filled('sucursal_id')) {
                    $q->where('sucursal_id', $request->sucursal_id);
                }
            });
        }

        $estancias = $estanciasQuery->get();
        Log::info('Estancias encontradas tras aplicar filtros: ' . $estancias->count());

        // 1. Total de Llegadas
        $totalLlegadas = $estancias->unique('persona_id')->count();

        $llegadasPorNacionalidadQuery = Estancia::with('persona.nacionalidad')
            ->whereBetween('fecha_hora_ingreso', [$fechaInicio, $fechaFin]);

        if ($request->filled('establecimiento_id')) {
            $llegadasPorNacionalidadQuery->whereHas('tipoCuarto', function ($subq) use ($request) {
                $subq->where('establecimiento_id', $request->establecimiento_id);
                if ($request->filled('sucursal_id')) {
                    $subq->where('sucursal_id', $request->sucursal_id);
                }
            });
        }
        $llegadasPorNacionalidad = $llegadasPorNacionalidadQuery
            ->get()
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
        if ($request->filled('establecimiento_id')) {
            $habitacionesDisponiblesQuery->where('establecimiento_id', $request->establecimiento_id);
        }
        if ($request->filled('sucursal_id')) {
            $habitacionesDisponiblesQuery->where('sucursal_id', $request->sucursal_id);
        }
        $totalHabitacionesDisponibles = $habitacionesDisponiblesQuery->sum('nro_habitaciones');

        $habitacionesOcupadas = $estancias->pluck('nro_cuarto')->unique()->count();

        $tasaOcupacion = $totalHabitacionesDisponibles > 0 ? ($habitacionesOcupadas / $totalHabitacionesDisponibles) * 100 : 0;

        $estadisticas = [
            'totalLlegadas' => $totalLlegadas,
            'llegadasPorNacionalidad' => $llegadasPorNacionalidad,
            'totalPernoctaciones' => $totalPernoctaciones,
            'estadiaMedia' => round($estadiaMedia, 2),
            'tasaOcupacion' => round($tasaOcupacion, 2),
            'habitacionesOcupadas' => $habitacionesOcupadas,
            'totalHabitacionesDisponibles' => $totalHabitacionesDisponibles,
        ];
        Log::info('Array de estadísticas calculado:', $estadisticas);
        Log::info('--- Fin de generación de estadísticas ---');

        $nacionalidades = Nacionalidad::select('id', 'gentilicio')->orderBy('gentilicio')->get();
        $establecimientos = Establecimiento::with(['sucursales' => function ($query) {
            $query->select('id_sucursal', 'id_casa_matriz', 'nombre_sucursal');
        }])->select('id_establecimiento', 'razon_social')->where('id_prestador', 5)->orderBy('razon_social')->get();

        return Inertia::render('Estadisticas/Index', [
            'nacionalidades' => $nacionalidades,
            'establecimientos' => $establecimientos,
            'estadisticas' => $estadisticas,
            'filters' => $request->only(['fecha_inicio', 'fecha_fin', 'nacionalidad_id', 'establecimiento_id', 'sucursal_id'])
        ]);
    }
}
