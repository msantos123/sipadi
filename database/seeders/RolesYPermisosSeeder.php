<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesYPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear Permisos
        Permission::create(['name' => 'gestionar-usuarios']);
        Permission::create(['name' => 'gestionar-empleados']);
        Permission::create(['name' => 'registrar-parte-diario']);
        Permission::create(['name' => 'aprobar-parte-diario']);
        Permission::create(['name' => 'ver-reportes-nacionales']);
        Permission::create(['name' => 'ver-reportes-especificos']);

        // Crear Roles y Asignar Permisos
        $roleRepresentante = Role::create(['name' => 'Representante Legal']);
        $roleRepresentante->givePermissionTo('gestionar-empleados');

        $roleOperador = Role::create(['name' => 'Operador']);
        $roleOperador->givePermissionTo('registrar-parte-diario');

        $roleGAD = Role::create(['name' => 'GAD']);
        $roleGAD->givePermissionTo('aprobar-parte-diario');

        $roleViceministerio = Role::create(['name' => 'Viceministerio']);
        $roleViceministerio->givePermissionTo([
            'gestionar-usuarios',
            'ver-reportes-nacionales',
            'ver-reportes-especificos'
        ]);

        $roleInstitucion = Role::create(['name' => 'Institucion']);
        $roleInstitucion->givePermissionTo('ver-reportes-especificos');

        // Crear Usuarios y Asignar Roles
        $userRepresentante = User::create([
            'nombres' => 'Usuario Representante',
            'apellido_paterno' => 'Legal',
            'apellido_materno' => 'Representante',
            'ci' => '2224567',
            'celular' => '12345678',
            'cargo' => 'Representante Legal',
            'email' => 'representante@example.com',
            'password' => Hash::make('12e45678')
        ]);
        $userRepresentante->assignRole($roleRepresentante);

        $userOperador = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Operador',
            'apellido_materno' => 'Sistema',
            'ci' => '2345678',
            'celular' => '12345678',
            'cargo' => 'Operador',
            'email' => 'operador@example.com',
            'password' => Hash::make('12e45678')
        ]);
        $userOperador->assignRole($roleOperador);

        $userGAD = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'GAD',
            'apellido_materno' => 'Local',
            'ci' => '3456789',
            'celular' => '98765432',
            'cargo' => 'Técnico GAD',
            'email' => 'gad@example.com',
            'password' => Hash::make('12e45678')
        ]);
        $userGAD->assignRole($roleGAD);

        $userViceministerio = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Viceministerio',
            'apellido_materno' => 'Nacional',
            'ci' => '4567890',
            'celular' => '55566677',
            'cargo' => 'Analista',
            'email' => 'viceministerio@example.com',
            'password' => Hash::make('12e45678')
        ]);
        $userViceministerio->assignRole($roleViceministerio);

        $userInstitucion = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Institucion',
            'apellido_materno' => 'Educativa',
            'ci' => '5678901',
            'celular' => '44455666',
            'cargo' => 'Técnico Institución',
            'email' => 'institucion@example.com',
            'password' => Hash::make('12e45678')
        ]);
        $userInstitucion->assignRole($roleInstitucion);
    }
}
