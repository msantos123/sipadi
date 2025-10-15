<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $usuarios = User::query()
            ->when($search, function ($query, $search) {
                return $query->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('ci', 'like', "%{$search}%")
                    ->orWhere('celular', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate(5)
            ->withQueryString();

        return inertia('usuarios/Index', [
            'usuarios' => $usuarios,
            'search' => $search,
        ]);
    }

    public function create()
    {
        return inertia('usuarios/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'nombres' => 'required|string|max:255',
            'ci' => 'required|string|max:20|unique:users',
            'celular' => 'required|string|max:15|unique:users',
            'cargo' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([

            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'nombres' => $request->nombres,
            'ci' => $request->ci,
            'celular' => $request->celular,
            'cargo' => $request->cargo,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        return inertia('usuarios/Edit', [
            'usuario' => $usuario,
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'nombres' => 'required|string|max:255',
            'ci' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($usuario->id)],
            'celular' => ['nullable', 'string', 'max:15', Rule::unique('users')->ignore($usuario->id)],
            'cargo' => 'required|string|max:100',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($usuario->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $usuario->update([
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'nombres' => $request->nombres,
            'ci' => $request->ci,
            'celular' => $request->celular,
            'cargo' => $request->cargo,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $usuario->update(['password' => bcrypt($request->password)]);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }
}
