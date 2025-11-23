<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Establecimiento;
use App\Models\Estancia;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;

class ReporteController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('id')->toArray();
        
        // Determinar alcance según rol
        // Admin (1) y Nacional (2) - Acceso total
        if (in_array(1, $userRoles) || in_array(2, $userRoles)) {
            $departamentos = Departamento::all();
            $establecimientos = Establecimiento::all();
            $sucursales = Sucursal::all();
            $alcance = 'nacional';
            
        // Departamental (4) - Solo su departamento
        } elseif (in_array(4, $userRoles)) {
            $departamentos = Departamento::where('id', $user->departamento_id)->get();
            $establecimientos = Establecimiento::where('id_departamento', $user->departamento_id)->where('id_prestador', 5)->get();
            $sucursales = Sucursal::where('id_departamento', $user->departamento_id)->get();
            $alcance = 'departamental';
            
        // Prestador (6) - Solo su establecimiento y sucursales
        } elseif (in_array(6, $userRoles)) {
            $departamentos = collect(); // Vacío
            $establecimientos = Establecimiento::where('id_establecimiento', $user->establecimiento_id)->get();
            $sucursales = Sucursal::where('id_casa_matriz', $user->establecimiento_id)->get();
            $alcance = 'establecimiento';
            
        } else {
            // Sin acceso
            abort(403, 'No tienes permiso para acceder a reportes');
        }

        return Inertia::render('Reportes/Index', [
            'departamentos' => $departamentos,
            'establecimientos' => $establecimientos,
            'sucursales' => $sucursales,
            'alcance' => $alcance,
        ]);
    }

    private function getReporteData(Request $request)
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('id')->toArray();
        
        $query = Estancia::with([
            'persona.nacionalidad',
            'persona.departamento',
            'lote.establecimiento',
            'lote.sucursal',
            'lote.departamento',
            'tipoCuarto',
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

        // Filtrar por rango de fechas
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_hora_ingreso', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Filtrar por múltiples departamentos
        if ($request->filled('departamento_ids')) {
            $departamentoIds = is_array($request->departamento_ids) 
                ? $request->departamento_ids 
                : [$request->departamento_ids];

            // Get IDs from the other database
            $establecimientoIds = Establecimiento::whereIn('id_departamento', $departamentoIds)->pluck('id_establecimiento');
            $sucursalIds = Sucursal::whereIn('id_departamento', $departamentoIds)->pluck('id_sucursal');

            if ($establecimientoIds->isNotEmpty() || $sucursalIds->isNotEmpty()) {
                $query->whereHas('lote', function ($loteQuery) use ($establecimientoIds, $sucursalIds) {
                    $loteQuery->where(function ($subQuery) use ($establecimientoIds, $sucursalIds) {
                        if ($establecimientoIds->isNotEmpty()) {
                            $subQuery->whereIn('establecimiento_id', $establecimientoIds);
                        }
                        if ($sucursalIds->isNotEmpty()) {
                            $subQuery->orWhereIn('sucursal_id', $sucursalIds);
                        }
                    });
                });
            } else {
                // If no establishments or sucursales match, return no results.
                $query->whereRaw('1 = 0');
            }
        }

        // Filtrar por múltiples establecimientos
        if ($request->filled('establecimiento_ids')) {
            $establecimientoIds = is_array($request->establecimiento_ids) 
                ? $request->establecimiento_ids 
                : [$request->establecimiento_ids];
                
            $query->whereHas('lote', function ($q) use ($establecimientoIds) {
                $q->whereIn('establecimiento_id', $establecimientoIds);
            });
        }

        // Filtrar por múltiples sucursales
        if ($request->filled('sucursal_ids')) {
            $sucursalIds = is_array($request->sucursal_ids) 
                ? $request->sucursal_ids 
                : [$request->sucursal_ids];
                
            $query->whereHas('lote', function ($q) use ($sucursalIds) {
                $q->whereIn('sucursal_id', $sucursalIds);
            });
        }

        return $query->get();
    }

    public function generarReporte(Request $request)
    {
        \Log::info('Datos recibidos en generarReporte:', $request->all());
        
        $datos = $this->getReporteData($request);
        
        \Log::info('Total de estancias encontradas:', ['count' => $datos->count()]);
        
        return response()->json($datos);
    }

    public function generarExcel(Request $request)
    {
        $estancias = $this->getReporteData($request);

        $data = $estancias->map(function ($estancia) {
            return [
                'Nombres' => $estancia->persona->nombres ?? '',
                'Apellido Paterno' => $estancia->persona->apellido_paterno ?? '',
                'Apellido Materno' => $estancia->persona->apellido_materno ?? '',
                'Nro Documento' => $estancia->persona->nro_documento ?? '',
                'Fecha Nacimiento' => $estancia->persona->fecha_nacimiento ? Carbon::parse($estancia->persona->fecha_nacimiento)->format('d/m/Y') : '',
                'Edad' => $estancia->persona->fecha_nacimiento ? Carbon::parse($estancia->persona->fecha_nacimiento)->age : '',
                'País' => $estancia->persona->nacionalidad->pais ?? '',
                'Departamento Origen' => $estancia->persona->departamento->nombre ?? '',
                'Ciudad Origen' => $estancia->persona->ciudad_origen ?? '',
                'Sexo' => $estancia->persona->sexo ?? '',
                'Estado Civil' => $estancia->persona->estado_civil ?? '',
                'Ocupación' => $estancia->persona->ocupacion ?? '',
                'Nro Cuarto' => $estancia->nro_cuarto ?? '',
                'Fecha Ingreso' => $estancia->fecha_hora_ingreso ? $estancia->fecha_hora_ingreso->format('d/m/Y H:i') : '',
                'Fecha Salida' => $estancia->fecha_hora_salida_efectiva ? $estancia->fecha_hora_salida_efectiva->format('d/m/Y H:i') : '',
                'Establecimiento Ciudad' => $estancia->lote->establecimiento->ciudad ?? '',
                'Establecimiento Razón Social' => $estancia->lote->establecimiento->razon_social ?? '',
                'Sucursal Ciudad' => $estancia->lote->sucursal->ciudad ?? '',
                'Sucursal Nombre' => $estancia->lote->sucursal->nombre_sucursal ?? '',
                'Tipo Cuarto' => $estancia->tipoCuarto->nombre ?? '',
            ];
        });

        return (new FastExcel($data))->download('reporte_estancias.xlsx');
    }
}
