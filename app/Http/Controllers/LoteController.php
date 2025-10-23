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
            'fecha_envio_operador' => now(),
        ]);

        return response()->json(['message' => 'Lote enviado a GAD para su revisión.']);
    }
    /**
     * Obtiene los lotes para revisión de GAD.
     */
    public function revisionGad()
    {
        // TODO: Añadir autorización para que solo usuarios GAD puedan ver esto.
        $lotes = Lote::where('estado_lote', 'EN_REVISION_GAD')
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

        // Verificar que todas las estancias han sido procesadas
        if ($lote->estancias()->where('estado_aprobacion_gad', 'EN_ESPERA')->exists()) {
            return response()->json(['message' => 'No todas las estancias de este lote han sido revisadas.'], 422);
        }

        DB::transaction(function () use ($lote) {
            // Actualizar el estado del lote
            $lote->update([
                'estado_lote' => 'EN_REVISION_VMT',
                'fecha_envio_gad' => now(),
            ]);

            // Actualizar las estancias aprobadas por GAD para que VMT pueda revisarlas
            $lote->estancias()
                ->where('estado_aprobacion_gad', 'APROBADO')
                ->update(['estado_aprobacion_vmt' => 'PENDIENTE']);
        });

        return response()->json(['message' => 'Lote enviado a VMT para su revisión.']);
    }

    public function confirmacionView()
    {
        return Inertia::render('Checkin/ViewConfirmacion');
    }

}
