<?php

namespace App\Http\Controllers;

use App\Models\Estancia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstanciaController extends Controller
{
    /**
     * Finaliza la estancia de un titular y sus dependientes.
     */
    public function checkout(Request $request, Estancia $estancia)
    {
        // Asegurarse de que estamos operando sobre un titular
        if (!$estancia->es_titular) {
            return response()->json(['message' => 'Esta acción solo puede realizarse sobre una estancia titular.'], 400);
        }

        $request->validate([
            'fecha_hora_salida_efectiva' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($estancia, $request) {
                // Actualizar la estancia del titular
                $estancia->update([
                    'fecha_hora_salida_efectiva' => $request->fecha_hora_salida_efectiva,
                    'estado_estancia' => 'FINALIZADA',
                ]);

                // Actualizar las estancias de los dependientes
                $estancia->dependientes()->update([
                    'fecha_hora_salida_efectiva' => $request->fecha_hora_salida_efectiva,
                    'estado_estancia' => 'FINALIZADA',
                ]);
            });

            return response()->json(['message' => 'Check-out realizado con éxito.']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ocurrió un error al realizar el check-out: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancela la estancia de un titular y sus dependientes.
     */
    public function cancel(Estancia $estancia)
    {
        // Asegurarse de que estamos operando sobre un titular
        if (!$estancia->es_titular) {
            return response()->json(['message' => 'Esta acción solo puede realizarse sobre una estancia titular.'], 400);
        }

        try {
            DB::transaction(function () use ($estancia) {
                // Cancelar la estancia del titular
                $estancia->update(['estado_estancia' => 'CANCELADA']);

                // Cancelar las estancias de los dependientes
                $estancia->dependientes()->update(['estado_estancia' => 'CANCELADA']);
            });

            return response()->json(['message' => 'Estancia cancelada con éxito.']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ocurrió un error al cancelar la estancia: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Aprueba una estancia por parte de GAD.
     */
    public function aprobarGad(Estancia $estancia)
    {
        // TODO: Autorización para asegurar que solo usuarios GAD pueden hacer esto.

        if ($estancia->estado_aprobacion_gad !== 'PENDIENTE') {
            return response()->json(['message' => 'Esta estancia no está pendiente de aprobación GAD.'], 422);
        }

        $estancia->update([
            'estado_aprobacion_gad' => 'APROBADO',
            'gad_usuario_id' => auth()->id(),
            'gad_fecha_aprobacion' => now(),
            'gad_observaciones' => null, // Limpiar observaciones por si acaso
        ]);

        return response()->json(['message' => 'Estancia aprobada por GAD.']);
    }

    /**
     * Rechaza una estancia por parte de GAD.
     */
    public function rechazarGad(Request $request, Estancia $estancia)
    {
        // TODO: Autorización para asegurar que solo usuarios GAD pueden hacer esto.

        $request->validate([
            'gad_observaciones' => 'required|string|max:500',
        ]);

        if ($estancia->estado_aprobacion_gad !== 'PENDIENTE') {
            return response()->json(['message' => 'Esta estancia no está pendiente de aprobación GAD.'], 422);
        }

        $estancia->update([
            'estado_aprobacion_gad' => 'RECHAZADO',
            'gad_observaciones' => $request->gad_observaciones,
            'gad_usuario_id' => auth()->id(),
            'gad_fecha_aprobacion' => now(),
        ]);

        return response()->json(['message' => 'Estancia rechazada por GAD.']);
    }
}