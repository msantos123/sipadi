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
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleAdmin->givePermissionTo([
            'gestionar-usuarios',
            'gestionar-empleados',
            'aprobar-parte-diario',
            'registrar-parte-diario',
            'ver-reportes-nacionales',
            'ver-reportes-especificos'
        ]);

        $roleNacional = Role::create(['name' => 'Nacional']);
        $roleNacional->givePermissionTo([
            'gestionar-usuarios',
            'ver-reportes-nacionales',
            'ver-reportes-especificos'
        ]);

        $roleDepartamental = Role::create(['name' => 'Departamental']);
        $roleDepartamental->givePermissionTo('aprobar-parte-diario');

        $rolePrestador = Role::create(['name' => 'Prestador']);
        $rolePrestador->givePermissionTo('gestionar-empleados');

        $rolePrestadoremp = Role::create(['name' => 'Prestadoremp']);
        $rolePrestadoremp->givePermissionTo('registrar-parte-diario');

        $roleInstitucion = Role::create(['name' => 'Institucion']);
        $roleInstitucion->givePermissionTo('ver-reportes-especificos');

                // 1. Usuario admin
        $userAdmin = User::create([
            'nombres' => 'admin',
            'apellido_paterno' => 'admin',
            'apellido_materno' => 'admin',
            'ci' => '2224567',
            'celular' => '12345678',
            'email' => 'admin@example.com',
            'password' => Hash::make('12e45678'),

            // Claves foráneas opcionales (deben existir en sus tablas respectivas)
            'nacionalidad_id' => 24,
            'departamento_id' => 2,
            'municipio_id' => 2,
        ]);
        // Asignación de rol usando el paquete (ej. Spatie)
        $userAdmin->assignRole($roleAdmin);

        // 2. Usuario nacional
        $userNacional = User::create([
            'nombres' => 'nacional',
            'apellido_paterno' => 'nacional',
            'apellido_materno' => 'nacional',
            'ci' => '2345678',
            'celular' => '12345678',
            'email' => 'nacional@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 4,
            'municipio_id' => 91,
        ]);
        $userNacional->assignRole($roleNacional);

        // 3. Usuario departamental
        $userDepartamental = User::create([
            'nombres' => 'departamental',
            'apellido_paterno' => 'departamental',
            'apellido_materno' => 'departamental',
            'ci' => '3456789',
            'celular' => '98765432',
            'email' => 'departamental@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 6,
            'municipio_id' => 146,
        ]);
        $userDepartamental->assignRole($roleDepartamental);

        // 4. Usuario prestador
        $userPrestador = User::create([
            'nombres' => 'prestador',
            'apellido_paterno' => 'prestador',
            'apellido_materno' => 'prestador',
            'ci' => '4567890',
            'celular' => '55566677',
            'email' => 'prestador@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 7,
            'municipio_id' => 190,
        ]);
        $userPrestador->assignRole($rolePrestador);

        // 5. Usuario prestadoremp
        $userPrestadoremp = User::create([
            'nombres' => 'prestadoremp',
            'apellido_paterno' => 'prestadoremp',
            'apellido_materno' => 'prestadoremp',
            'ci' => '5678901',
            'celular' => '44455666',
            'email' => 'prestadoremp@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 5,
            'municipio_id' => 220,
        ]);
        $userPrestadoremp->assignRole($rolePrestadoremp);

                // 6. Usuario Institución
        $userInstitucion = User::create([
            'nombres' => 'institucion',
            'apellido_paterno' => 'institucion',
            'apellido_materno' => 'institucion',
            'ci' => '5608901',
            'celular' => '44455666',
            'email' => 'institucion@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 5,
            'municipio_id' => 220,
        ]);
        $userInstitucion->assignRole($roleInstitucion);
    }
}
