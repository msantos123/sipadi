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
        $departamentos = Departamento::all();
        $establecimientos = Establecimiento::all();
        $sucursales = Sucursal::all();

        return Inertia::render('Reportes/Index', [
            'departamentos' => $departamentos,
            'establecimientos' => $establecimientos,
            'sucursales' => $sucursales,
        ]);
    }

    private function getReporteData(Request $request)
    {
        $query = Estancia::with([
            'persona.nacionalidad',
            'persona.departamento',
            'lote.establecimiento',
            'lote.sucursal',
            'tipoCuarto',
        ]);

        // Filtrar por rango de fechas
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_hora_ingreso', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Filtrar por departamento de establecimiento o sucursal
        if ($request->filled('departamento_id')) {
            $departamentoId = $request->departamento_id;

            // Get IDs from the other database
            $establecimientoIds = Establecimiento::where('id_departamento', $departamentoId)->pluck('id_establecimiento');
            $sucursalIds = Sucursal::where('id_departamento', $departamentoId)->pluck('id_sucursal');

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

        // Filtrar por establecimiento
        if ($request->filled('establecimiento_id')) {
            $query->whereHas('lote', function ($q) use ($request) {
                $q->where('establecimiento_id', $request->establecimiento_id);
            });
        }

        // Filtrar por sucursal
        if ($request->filled('sucursal_id')) {
            $query->whereHas('lote', function ($q) use ($request) {
                $q->where('sucursal_id', $request->sucursal_id);
            });
        }

        return $query->get();
    }

    public function generarReporte(Request $request)
    {
        $datos = $this->getReporteData($request);

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
