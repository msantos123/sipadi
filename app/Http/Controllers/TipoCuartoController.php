<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\Sucursal;
use App\Models\TipoCuarto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TipoCuartoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $locations = collect([]);
        $userEstablecimientoId = $user->establecimiento_id;
        //dd($userEstablecimientoId);
        if ($userEstablecimientoId) {
            // 1. Buscar la casa matriz
            $casaMatriz = Establecimiento::find($userEstablecimientoId);
            if ($casaMatriz) {
                $locations->push([
                    'id' => 'est-' . $casaMatriz->id_establecimiento,
                    'nombre' => $casaMatriz->razon_social . ' (Casa Matriz)',
                ]);
            }

            // 2. Buscar sucursales de esa casa matriz
            $sucursales = Sucursal::where('id_casa_matriz', $userEstablecimientoId)->get();
            foreach ($sucursales as $sucursal) {
                $locations->push([
                    'id' => 'suc-' . $sucursal->id_sucursal,
                    'nombre' => $sucursal->nombre_sucursal . ' (Sucursal)',
                ]);
            }
        }

        $selected_location_id = $request->input('location_id');
        $tiposRequeridos = ['Simple', 'Doble', 'Triple', 'Cuadruple', 'Familiar'];
        $cuartosData = collect([]);

        if ($selected_location_id) {
            [$type, $id] = explode('-', $selected_location_id);

            $query = TipoCuarto::query();

            if ($type === 'est') {
                $query->where('establecimiento_id', $id)->whereNull('sucursal_id');
            } elseif ($type === 'suc') {
                $query->where('sucursal_id', $id);
            }
            $cuartosExistentes = $query->get()->keyBy('nombre');

            $cuartosData = collect($tiposRequeridos)->map(function ($nombre) use ($cuartosExistentes) {
                $existente = $cuartosExistentes->get($nombre);
                return [
                    'nombre' => $nombre,
                    'nro_habitaciones' => $existente ? $existente->nro_habitaciones : 0,
                    'nro_personas' => $existente ? $existente->nro_personas : 0,
                ];
            });
        }

        return Inertia::render('Cuartos/Index', [
            'locations' => $locations,
            'cuartos' => $cuartosData,
            'selected_location_id' => $selected_location_id
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cuartos' => 'required|array|min:5',
            'cuartos.*.nombre' => 'required|string',
            'cuartos.*.nro_habitaciones' => 'required|integer|min:0',
            'cuartos.*.nro_personas' => 'required|integer|min:0',
            'location_id' => 'required|string',
        ]);

        [$type, $id] = explode('-', $validated['location_id']);

        foreach ($validated['cuartos'] as $cuartoData) {
            $attributes = [
                'nombre' => $cuartoData['nombre'],
            ];

            if ($type === 'est') {
                $attributes['establecimiento_id'] = $id;
                $attributes['sucursal_id'] = null;
            } elseif ($type === 'suc') {
                $sucursal = Sucursal::findOrFail($id);
                $attributes['establecimiento_id'] = $sucursal->id_casa_matriz;
                $attributes['sucursal_id'] = $id;
            }

            TipoCuarto::updateOrCreate(
                $attributes,
                [
                    'nro_habitaciones' => $cuartoData['nro_habitaciones'],
                    'nro_personas' => $cuartoData['nro_personas'],
                ]
            );
        }

        return redirect()->route('cuartos.index', ['location_id' => $validated['location_id']])->with('success', 'Tipos de cuarto guardados exitosamente.');
    }
}
