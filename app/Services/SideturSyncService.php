<?php

namespace App\Services;

use App\Models\Departamento;
use App\Models\Nacionalidad;
use App\Models\Establecimiento;
use Spatie\Permission\Models\Role;
use App\Models\Sidetur\SideturUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SideturSyncService
{
    /**
     * Sincroniza un usuario de Sidetur a la base de datos de SIPADI.
     *
     * @param string $identificator (email o ci)
     * @return User|null
     */
    public function syncUser(string $identificator): ?User
    {
        // 1. Buscar si el usuario ya existe en SIPADI (por CI o email)
        $sipadiUser = User::where('email', $identificator)
            ->orWhere('ci', $identificator)
            ->first();

        if ($sipadiUser) {
            Log::info('Usuario encontrado en SIPADI. No se necesita sincronización.', ['identificator' => $identificator, 'source' => 'sipadi']);
            return $sipadiUser; // Retornar el usuario existente
        }

        // 2. Si no existe en SIPADI, buscar el usuario en Sidetur
        $sideturUser = SideturUser::where('email', $identificator)
            ->orWhere('cedula_identidad', $identificator)
            ->first();

        if (! $sideturUser) {
            Log::warning('Usuario no encontrado ni en SIPADI ni en Sidetur.', ['identificator' => $identificator]);
            return null; // Usuario no encontrado en ninguna de las dos bases de datos
        }

        // 2.1. Doble chequeo para evitar duplicados con datos de Sidetur
        $ciCompleto = $sideturUser->cedula_identidad . ($sideturUser->complemento ? '-' . $sideturUser->complemento : '');
        $existingSipadiUser = User::where('email', $sideturUser->email)
            ->orWhere('ci', $ciCompleto)
            ->first();

        if ($existingSipadiUser) {
            Log::info('Usuario encontrado en SIPADI con datos de Sidetur. No se necesita sincronización.', ['email' => $existingSipadiUser->email, 'source' => 'sipadi']);
            return $existingSipadiUser; // Retornar el usuario existente
        }

        // 3. Si no existe, proceder con la creación (migración JIT)
        Log::info('Usuario no encontrado en SIPADI. Creando nuevo usuario desde Sidetur.', ['email' => $sideturUser->email, 'source' => 'sidetur']);

        // 3.1. Sincronizar Establecimiento
        $establecimientoId = null;

        // Usamos una consulta directa porque el modelo SideturEstablecimiento no fue creado.
        $sideturEstablecimiento = DB::connection('mysql_sidetur')->table('establecimiento')
            ->where('id_usuario', $sideturUser->id) // Asumiendo que la PK de sideturUser es id_usuario
            ->where('id_prestador', 5)
            ->first();
        //dd($sideturEstablecimientoData);
        if ($sideturEstablecimiento) {
            // Obtenemos el ID del establecimiento directamente de Sidetur
            $establecimientoId = $sideturEstablecimiento->id_establecimiento;
            Log::info('Establecimiento encontrado en Sidetur.', ['id' => $establecimientoId]);
        } else {
            Log::warning('El usuario de Sidetur no tiene un establecimiento asociado...', ['sidetur_user_id' => $sideturUser->id_usuario]);
        }

        // 3.2. Mapeo de Rol
        $rolId = $this->getSipadiRolId($sideturUser);
        Log::info('numero de usuario.', ['id' => $rolId]);
        // 3.3. Mapeo de Nacionalidad (case-insensitive)
        $nacionalidadId = null;
        if ($sideturUser->nacionalidad) {
            $nacionalidad = Nacionalidad::where('gentilicio', 'like', $sideturUser->nacionalidad)->first();
            $nacionalidadId = $nacionalidad ? $nacionalidad->id : null;
        }

        // 3.4. Mapeo de Departamento (ID directo)
        $departamentoId = $sideturUser->departamento;

        // 4. Crear el nuevo usuario en SIPADI
        $newSipadiUser = User::create([
            'apellido_paterno' => $sideturUser->apellido_paterno,
            'apellido_materno' => $sideturUser->apellido_materno,
            'nombres' => $sideturUser->nombre,
            'ci' => $ciCompleto,
            'celular' => $sideturUser->numero_celular,
            'nacionalidad_id' => $nacionalidadId,
            'departamento_id' => $departamentoId,
            'municipio_id' => null, // Se establece a null como solicitado
            'establecimiento_id' => $establecimientoId, // ID del establecimiento sincronizado
            'estado' => $sideturUser->active == 1 ? 'activo' : 'inactivo',
            'email' => $sideturUser->email,
            'password' => $sideturUser->password, // Copiar el hash directamente
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        // 5. Asignar rol con Spatie
        if ($rolId) {
            $newSipadiUser->assignRole($rolId);
        }

        return $newSipadiUser;
    }

    private function getSipadiRolId(SideturUser $sideturUser): ?int
    {
        // Asumimos que el ID del grupo en Sidetur corresponde directamente al rol_id en Sipadi.
        $group = $sideturUser->groups()->first();
        Log::info('numero de usuario de la funcion.', ['id' => $group]);
        // Si el usuario tiene un grupo, devolvemos su ID.
        if ($group) {
            return $group->id;
        }

        // Si no se encuentra ningún grupo, se devuelve null.
        // El proceso de creación del usuario debería manejar este caso.
        return null;
    }
}
