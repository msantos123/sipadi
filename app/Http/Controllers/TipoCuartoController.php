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
        $cuartosData = collect([]);

        if ($selected_location_id) {
            [$type, $id] = explode('-', $selected_location_id);

            $query = TipoCuarto::query();

            if ($type === 'est') {
                $query->where('establecimiento_id', $id)->whereNull('sucursal_id');
            } elseif ($type === 'suc') {
                $query->where('sucursal_id', $id);
            }
            
            // Cargar todos los tipos existentes para esta ubicación
            $cuartosData = $query->get()->map(function ($cuarto) {
                return [
                    'nombre' => $cuarto->nombre,
                    'nro_habitaciones' => $cuarto->nro_habitaciones,
                    'nro_personas' => $cuarto->nro_personas,
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
            'cuartos' => 'required|array',
            'cuartos.*.nombre' => 'required|string',
            'cuartos.*.nro_habitaciones' => 'required|integer|min:0',
            'cuartos.*.nro_personas' => 'required|integer|min:0',
            'location_id' => 'required|string',
        ]);

        [$type, $id] = explode('-', $validated['location_id']);

        // Determinar los atributos de ubicación
        $locationAttributes = [];
        if ($type === 'est') {
            $locationAttributes['establecimiento_id'] = $id;
            $locationAttributes['sucursal_id'] = null;
        } elseif ($type === 'suc') {
            $sucursal = Sucursal::findOrFail($id);
            $locationAttributes['establecimiento_id'] = $sucursal->id_casa_matriz;
            $locationAttributes['sucursal_id'] = $id;
        }

        // Obtener nombres de los tipos enviados
        $nombresEnviados = collect($validated['cuartos'])->pluck('nombre')->toArray();

        // Eliminar tipos que ya no están en la lista
        TipoCuarto::where($locationAttributes)
            ->whereNotIn('nombre', $nombresEnviados)
            ->delete();

        // Actualizar o crear los tipos enviados
        foreach ($validated['cuartos'] as $cuartoData) {
            $attributes = array_merge($locationAttributes, [
                'nombre' => $cuartoData['nombre'],
            ]);

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
