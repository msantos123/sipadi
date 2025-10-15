<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Nacionalidad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservaViewController extends Controller
{
    public function create()
    {
        return Inertia::render('Reservas/NuevaReservaView', [
            'nacionalidades' => Nacionalidad::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
        ]);
    }
}
