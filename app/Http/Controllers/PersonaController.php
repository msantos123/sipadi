<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PersonaController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellido_paterno' => 'nullable|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'tipo_documento' => 'required|string|in:ci,pasaporte',
            'nro_documento' => 'required|string|max:255|unique:personas,nro_documento',
            'complemento' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'nacionalidad_id' => 'required|exists:nacionalidades,id',
            'departamento_id' => 'nullable|required_if:nacionalidad_id,24|exists:departamentos,id',
            'municipio_id' => 'nullable|required_if:nacionalidad_id,24|exists:municipios,id',
            'ciudad_origen' => 'nullable|required_unless:nacionalidad_id,24|string|max:255',
            'sexo' => 'required|string|in:M,F,O',
            'estado_civil' => 'required|string|max:255',
            'ocupacion' => 'nullable|string|max:255',
        ]);

        if ($validatedData['nacionalidad_id'] == 24) { // 24 is the ID for Bolivia
            $validatedData['ciudad_origen'] = null;
        } else {
            $validatedData['departamento_id'] = null;
            $validatedData['municipio_id'] = null;
        }

        $persona = Persona::create($validatedData);

        return Redirect::back()->with('success', 'Persona creada con éxito.')->with('persona', $persona);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Persona $persona)
    {
        $validatedData = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellido_paterno' => 'nullable|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'tipo_documento' => 'required|string|in:ci,pasaporte',
            'nro_documento' => 'required|string|max:255|unique:personas,nro_documento,' . $persona->id,
            'complemento' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'nacionalidad_id' => 'required|exists:nacionalidades,id',
            'departamento_id' => 'nullable|required_if:nacionalidad_id,24|exists:departamentos,id',
            'municipio_id' => 'nullable|required_if:nacionalidad_id,24|exists:municipios,id',
            'ciudad_origen' => 'nullable|required_unless:nacionalidad_id,24|string|max:255',
            'sexo' => 'required|string|in:M,F,O',
            'estado_civil' => 'required|string|max:255',
            'ocupacion' => 'nullable|string|max:255',
        ]);

        if ($validatedData['nacionalidad_id'] == 24) { // 24 is the ID for Bolivia
            $validatedData['ciudad_origen'] = null;
        } else {
            $validatedData['departamento_id'] = null;
            $validatedData['municipio_id'] = null;
        }

        $persona->update($validatedData);

        return Redirect::back()->with('success', 'Persona actualizada con éxito.')->with('persona', $persona);
    }

    public function search(Request $request)
    {
        $request->validate([
            'documento' => 'required|string|min:3',
        ]);

        $query = $request->input('documento');

        $personas = Persona::where('nro_documento', 'like', "{$query}%")
            ->select(['id', 'nombres', 'apellido_paterno', 'apellido_materno', 'nro_documento'])
            ->take(10)
            ->get();

        // Inertia procesará esta respuesta JSON en el callback `onSuccess`
        return response()->json($personas);
    }
}
