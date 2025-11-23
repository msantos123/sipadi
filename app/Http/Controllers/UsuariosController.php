<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Nacionalidad;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Mail\UserVerificationMail;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $authUser = auth()->user();

        $query = User::with(['roles', 'establecimiento', 'sucursal']); // Se inicia la consulta con roles, establecimiento y sucursal

        // ----> INICIO DE LA LÓGICA DE PERMISOS <----
        if ($authUser->can('gestionar-empleados')) {
            $query->where('establecimiento_id', $authUser->establecimiento_id)
                  ->whereHas('roles', function ($q) {
              $q->where('name', 'Prestadoremp');
          });
        } elseif ($authUser->can('gestionar-usuarios')) {
            $query->whereNull('establecimiento_id');
        }
        // ----> FIN DE LA LÓGICA DE PERMISOS <----

        // Se aplica la búsqueda sobre la consulta ya filtrada por permisos
        $usuarios = $query->when($search, function ($q, $search) {
                // Se agrupan los 'orWhere' para no interferir con los filtros principales
                return $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellido_paterno', 'like', "%{$search}%")
                        ->orWhere('apellido_materno', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%")
                        ->orWhere('celular', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
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
        $user = Auth::user()->load('establecimiento.sucursales');
        return inertia('usuarios/CreateEmpleados', [
            'nacionalidades' => Nacionalidad::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'establecimiento' => $user->establecimiento,
        ]);
    }

    public function createUsuario()
    {
        return inertia('usuarios/CreateUsuarios', [
            'nacionalidades' => Nacionalidad::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'roles' => Role::all()
        ]);
    }

    public function store(Request $request)
    {
        // Reglas de validación comunes
        $validationRules = [
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'nombres' => 'required|string|max:100',
            'ci' => 'required|string|max:20|unique:users',
            'celular' => 'nullable|string|max:15',
            'nacionalidad_id' => 'nullable|exists:nacionalidades,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'email' => 'required|string|email|max:100|unique:users',
        ];

        // Generar contraseña automática de 8 caracteres
        $generatedPassword = strtoupper(Str::random(8));

        // Datos comunes de usuario
        $userData = [
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'nombres' => $request->nombres,
            'ci' => $request->ci,
            'celular' => $request->celular,
            'nacionalidad_id' => $request->nacionalidad_id,
            'departamento_id' => $request->departamento_id,
            'municipio_id' => $request->municipio_id,
            'email' => $request->email,
            'password' => $generatedPassword,
            'must_change_password' => true,
        ];

        // Si 'asignar_a' está presente, es un empleado de establecimiento
        if ($request->has('asignar_a')) {
            $request->validate(array_merge($validationRules, [
                'asignar_a' => 'required|string',
            ]));

            $sucursal_id = null;
            $establecimiento_id = Auth::user()->establecimiento_id;

            if (Str::startsWith($request->asignar_a, 'suc_')) {
                $sucursal_id = (int) Str::after($request->asignar_a, 'suc_');
            }

            $userData['establecimiento_id'] = $establecimiento_id;
            $userData['sucursal_id'] = $sucursal_id;

            $user = User::create($userData);
            $user->assignRole('Prestadoremp');
        } else {
            // Es un usuario general (administrativo)
            $request->validate(array_merge($validationRules, [
                'role_id' => 'required|exists:roles,id',
            ]));

            $userData['establecimiento_id'] = null;
            $userData['sucursal_id'] = null;

            $user = User::create($userData);
            $role = Role::findById($request->role_id);
            $user->assignRole($role);
        }

        // Guardar el código de verificación (mismo que la contraseña)
        $user->verification_code = $generatedPassword;
        $user->verification_code_expires_at = now()->addHours(24);
        $user->save();
        
        // Enviar correo con la contraseña
        Mail::to($user->email)->queue(new UserVerificationMail($user, $generatedPassword));

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function storeUsuario(Request $request)
    {
        $request->validate([
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'nombres' => 'required|string|max:100',
            'ci' => 'required|string|max:20|unique:users',
            'celular' => 'nullable|string|max:15',
            'nacionalidad_id' => 'nullable|exists:nacionalidades,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'email' => 'required|string|email|max:100|unique:users',
            'role_id' => 'required|string',
        ]);

        // Generar contraseña automática de 8 caracteres
        $generatedPassword = strtoupper(Str::random(8));

        $user = User::create([
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'nombres' => $request->nombres,
            'ci' => $request->ci,
            'celular' => $request->celular,
            'nacionalidad_id' => $request->nacionalidad_id,
            'departamento_id' => $request->departamento_id,
            'municipio_id' => $request->municipio_id,
            'establecimiento_id' => null,
            'sucursal_id' => null,
            'email' => $request->email,
            'password' => $generatedPassword,
            'must_change_password' => true,

        ]);

        $rol = $request->role_id;

        $user->assignRole($rol);

        // Guardar el código de verificación (mismo que la contraseña)
        $user->verification_code = $generatedPassword;
        $user->verification_code_expires_at = now()->addHours(24);
        $user->save();
        
        // Enviar correo con la contraseña
        Mail::to($user->email)->queue(new UserVerificationMail($user, $generatedPassword));

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function edit($id)
    {
        $usuario = User::findOrFail($id)->load('roles');
        $authUser = Auth::user()->load('establecimiento.sucursales');

        $establecimiento = null;
        if ($authUser->can('gestionar-empleados')) {
            $establecimiento = $authUser->establecimiento;
        }

        return inertia('usuarios/Edit', [
            'usuario' => $usuario,
            'nacionalidades' => Nacionalidad::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'roles' => Role::whereNotIn('id', [4, 5])->get(),
            'establecimiento' => $establecimiento,
        ]);
    }

    public function update(Request $request, $id)
    {
        //dd($request);
        $usuario = User::findOrFail($id);

        // Reglas de validación base, ignorando el usuario actual en campos únicos
        $validationRules = [
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'nombres' => 'required|string|max:100',
            'ci' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($usuario->id)],
            'celular' => ['nullable', 'string', 'max:15'],
            'nacionalidad_id' => 'nullable|exists:nacionalidades,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users')->ignore($usuario->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ];

        // Datos base del usuario a actualizar
        $userData = $request->only([
            'apellido_paterno', 'apellido_materno', 'nombres', 'ci', 'celular',
            'nacionalidad_id', 'departamento_id', 'municipio_id', 'email'
        ]);

        // Lógica para diferenciar entre Empleado y Usuario General
        if ($request->filled('asignar_a')) {
            // Es un empleado de establecimiento
            $request->validate(array_merge($validationRules, [
                'asignar_a' => 'required|string',
            ]));

            $sucursal_id = null;
            // El establecimiento_id se mantiene, ya que un empleado no puede cambiar de establecimiento
            $establecimiento_id = $usuario->establecimiento_id;

            if (Str::startsWith($request->asignar_a, 'suc_')) {
                $sucursal_id = (int) Str::after($request->asignar_a, 'suc_');
            }

            $userData['sucursal_id'] = $sucursal_id;
            $userData['establecimiento_id'] = $establecimiento_id; // Asegurarse de que se mantenga

            $usuario->update($userData);

        } else {
            // Es un usuario general (administrativo)
            $request->validate(array_merge($validationRules, [
                'role_id' => 'required|exists:roles,id',
            ]));

            $userData['establecimiento_id'] = null;
            $userData['sucursal_id'] = null;

            $usuario->update($userData);
            $role = Role::findById($request->role_id);
            if ($role) {
                $usuario->syncRoles($role);
            }
        }

        // Actualizar contraseña si se proporcionó una nueva
        if ($request->filled('password')) {
            $usuario->update(['password' => $request->password]);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function toggleEstado(User $user)
    {
        $authUser = auth()->user();

        if (! $authUser->can('gestionar-empleados') && ! $authUser->can('gestionar-usuarios')) {
            abort(403);
        }

        if ($authUser->can('gestionar-empleados') && $user->establecimiento_id !== $authUser->establecimiento_id) {
            abort(403);
        }

        $user->estado = $user->estado === 'activo' ? 'inactivo' : 'activo';
        $user->save();

        return back()->with('success', 'Estado del usuario actualizado correctamente.');
    }
}
