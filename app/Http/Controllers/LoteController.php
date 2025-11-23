<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LoteController extends Controller
{
    /**
     * Muestra la página de revisión de lotes.
     */
    public function revisionView()
    {
        return Inertia::render('Checkin/ViewRevision');
    }

    /**
     * Envía un lote a revisión de GAD.
     */
    public function submitToGad(Lote $lote)
    {
        // Verificar que el usuario que envía es el que creó el lote.
        if ($lote->usuario_registra_id !== auth()->id()) {
            return response()->json(['message' => 'No está autorizado para realizar esta acción.'], 403);
        }

        // Verificar que el lote esté en el estado correcto para ser enviado.
        if ($lote->estado_lote !== 'PENDIENTE_DE_ENVIO') {
            return response()->json(['message' => 'Este lote no se puede enviar. Se esperaba el estado "PENDIENTE_DE_ENVIO", pero se encontró el estado "'.$lote->estado_lote.'".'], 422);
        }
        $lote->update([
            'estado_lote' => 'EN_REVISION_GAD',
            'fecha_envio_gad' => now(),
        ]);

        return response()->json(['message' => 'Lote enviado a GAD para su revisión.']);
    }
    /**
     * Obtiene los lotes para revisión de GAD.
     */
    public function revisionGad()
    {
        // TODO: Añadir autorización para que solo usuarios GAD puedan ver esto.
        
        $user = auth()->user();
        
        // Filtrar lotes por departamento del usuario
        $query = Lote::whereIn('estado_lote', ['EN_REVISION_GAD', 'EN_REVISION_VMT', 'COMPLETADO']);
        
        // Si el usuario tiene departamento asignado, filtrar por ese departamento
        if ($user->departamento_id) {
            $query->where('departamento_id', $user->departamento_id);
        }
        
        $lotes = $query
            ->with('establecimiento')
            ->with('sucursales')
            ->with('usuarioRegistra') // Carga la relación para obtener el nombre
            ->orderBy('fecha_lote', 'desc')
            ->get();

        return response()->json($lotes);
    }

    /**
     * Obtiene todas las estancias asociadas a un lote específico.
     */
    public function getEstancias(Lote $lote)
    {
        // TODO: Añadir autorización para asegurarse de que solo los usuarios correctos (GAD/VMT)
        // puedan ver las estancias según el estado del lote.

        $estancias = $lote->estancias()->with('persona.nacionalidad')->get();

        return response()->json($estancias);
    }

    /**
     * Envía un lote a revisión de VMT después de la revisión de GAD.
     */
    public function submitToVmt(Lote $lote)
    {
        // TODO: Autorización para asegurar que solo usuarios GAD pueden hacer esto.

        if ($lote->estado_lote !== 'EN_REVISION_GAD') {
            return response()->json(['message' => 'Este lote no está en revisión por GAD.'], 422);
        }

        $lote->update([
            'estado_lote' => 'EN_REVISION_VMT',
            'fecha_envio_nacional' => now(),
        ]);

        return response()->json(['message' => 'Lote enviado a VMT para su revisión.']);
    }

    public function confirmacionView()
    {
        return Inertia::render('Checkin/ViewConfirmacion');
    }

    /**
     * Cambia el estado de múltiples lotes.
     */
    public function cambiarEstadoMultiple(Request $request)
    {
        $validated = $request->validate([
            'lote_ids' => 'required|array',
            'lote_ids.*' => 'exists:lotes,id',
            'estado' => 'required|in:PENDIENTE_DE_ENVIO,EN_REVISION_GAD,EN_REVISION_VMT,COMPLETADO'
        ]);

        // Actualizar todos los lotes seleccionados
        Lote::whereIn('id', $validated['lote_ids'])
            ->update([
                'estado_lote' => $validated['estado'],
                'fecha_envio_nacional' => $validated['estado'] === 'EN_REVISION_VMT' ? now() : null
            ]);

        return response()->json([
            'message' => 'Estados actualizados exitosamente',
            'count' => count($validated['lote_ids'])
        ]);
    }

}
