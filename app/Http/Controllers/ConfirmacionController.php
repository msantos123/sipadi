<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Lote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfirmacionController extends Controller
{
    /**
     * Muestra la página de confirmación de lotes.
     */
    public function index()
    {
        return Inertia::render('Checkin/ViewConfirmacion', [
            'departamentos' => Departamento::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Obtiene los lotes para revisión de VMT según los filtros.
     */
    public function revisionVmt(Request $request)
    {
        $query = Lote::query();

        // Filtrar por estado del lote
        $query->whereIn('estado_lote', ['EN_REVISION_VMT', 'COMPLETADO']);

        // Filtrar por fecha si se proporciona
        if ($request->has('fecha_lote') && $request->fecha_lote) {
            $query->whereDate('fecha_lote', $request->fecha_lote);
        }

        // Filtrar por departamento si se proporciona
        if ($request->has('departamento_id') && $request->departamento_id) {
            $query->where('departamento_id', $request->departamento_id);
        }

        $lotes = $query->with('departamento', 'usuarioRegistra', 'establecimiento', 'sucursales')
            ->orderBy('fecha_lote', 'desc')
            ->get();

        return response()->json($lotes);
    }

    /**
     * Marca un lote como completado después de la revisión de VMT.
     */
    public function completar(Lote $lote)
    {
        // TODO: Añadir autorización para VMT

        if ($lote->estado_lote !== 'EN_REVISION_VMT') {
            return response()->json(['message' => 'Este lote no está en revisión por VMT.'], 422);
        }

        $lote->update([
            'estado_lote' => 'COMPLETADO',
            'fecha_envio_completado' => now(), // Opcional: usar un campo dedicado si existe
        ]);

        return response()->json(['message' => 'Lote completado con éxito.']);
    }
}
