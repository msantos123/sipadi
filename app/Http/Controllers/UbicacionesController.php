<?php

namespace App\Http\Controllers;

use App\Models\Nacionalidad;
use App\Models\Departamento;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UbicacionesController extends Controller
{
    public function index()
    {
        return Inertia::render('Ubicaciones/Index', [
            'nacionalidades' => Nacionalidad::orderBy('gentilicio')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'municipios' => Municipio::with('departamento')->orderBy('nombre_municipio')->get(),
        ]);
    }

    // ============ NACIONALIDADES ============
    
    public function storeNacionalidad(Request $request)
    {
        $validated = $request->validate([
            'pais' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'gentilicio' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'codigo_nacionalidad' => 'required|string|size:3|regex:/^[A-Z]{3}$/',
        ]);

        Nacionalidad::create($validated);

        return redirect()->back()->with('success', 'Nacionalidad creada correctamente');
    }

    public function updateNacionalidad(Request $request, Nacionalidad $nacionalidad)
    {
        $validated = $request->validate([
            'pais' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'gentilicio' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'codigo_nacionalidad' => 'required|string|size:3|regex:/^[A-Z]{3}$/',
        ]);

        $nacionalidad->update($validated);

        return redirect()->back()->with('success', 'Nacionalidad actualizada correctamente');
    }

    public function destroyNacionalidad(Nacionalidad $nacionalidad)
    {
        try {
            $nacionalidad->delete();
            return redirect()->back()->with('success', 'Nacionalidad eliminada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se puede eliminar esta nacionalidad porque está en uso');
        }
    }

    // ============ DEPARTAMENTOS ============
    
    public function storeDepartamento(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:departamentos,nombre|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'sigla' => 'required|string|size:2|regex:/^[A-Z]{2}$/',
        ]);

        Departamento::create($validated);

        return redirect()->back()->with('success', 'Departamento creado correctamente');
    }

    public function updateDepartamento(Request $request, Departamento $departamento)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:departamentos,nombre,' . $departamento->id . '|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'sigla' => 'required|string|size:2|regex:/^[A-Z]{2}$/',
        ]);

        $departamento->update($validated);

        return redirect()->back()->with('success', 'Departamento actualizado correctamente');
    }

    public function destroyDepartamento(Departamento $departamento)
    {
        try {
            $departamento->delete();
            return redirect()->back()->with('success', 'Departamento eliminado correctamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se puede eliminar este departamento porque está en uso');
        }
    }

    // ============ MUNICIPIOS ============
    
    public function storeMunicipio(Request $request)
    {
        $validated = $request->validate([
            'nombre_municipio' => 'required|string|max:255|regex:/^[A-ZÁÉÍÓÚÑ\s]+$/',
            'departamento_id' => 'required|exists:departamentos,id',
            'codigo_municipio' => 'required|string|max:10',
        ]);

        Municipio::create($validated);

        return redirect()->back()->with('success', 'Municipio creado correctamente');
    }

    public function updateMunicipio(Request $request, Municipio $municipio)
    {
        $validated = $request->validate([
            'nombre_municipio' => 'required|string|max:255|regex:/^[A-ZÁÉÍÓÚÑ\s]+$/',
            'departamento_id' => 'required|exists:departamentos,id',
            'codigo_municipio' => 'required|string|max:10',
        ]);

        $municipio->update($validated);

        return redirect()->back()->with('success', 'Municipio actualizado correctamente');
    }

    public function destroyMunicipio(Municipio $municipio)
    {
        try {
            $municipio->delete();
            return redirect()->back()->with('success', 'Municipio eliminado correctamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se puede eliminar este municipio porque está en uso');
        }
    }
}
