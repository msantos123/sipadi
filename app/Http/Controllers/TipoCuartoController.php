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
        $cuartos = [];

        if ($selected_location_id) {
            [$type, $id] = explode('-', $selected_location_id);

            $query = TipoCuarto::query();

            if ($type === 'est') {
                $query->where('establecimiento_id', $id);
            } elseif ($type === 'suc') {
                $query->where('sucursal_id', $id);
            }
            $cuartos = $query->get();
        }

        return Inertia::render('Cuartos/Index', [
            'locations' => $locations,
            'cuartos' => $cuartos,
            'selected_location_id' => $selected_location_id
        ]);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'nro_habitaciones' => 'required|integer|min:1',
            'nro_personas' => 'required|integer|min:1',
            'location_id' => 'required|string',
        ]);

        [$type, $id] = explode('-', $validated['location_id']);

        $data = [
            'nombre' => $validated['nombre'],
            'nro_habitaciones' => $validated['nro_habitaciones'],
            'nro_personas' => $validated['nro_personas'],
        ];

        if ($type === 'est') {
            $data['establecimiento_id'] = $id;
        } elseif ($type === 'suc') {
            $data['establecimiento_id'] = $id;
            $data['sucursal_id'] = $id;
        }

        TipoCuarto::create($data);

        return redirect()->route('cuartos.index', ['location_id' => $validated['location_id']])->with('success', 'Cuarto agregado exitosamente.');
    }
}
