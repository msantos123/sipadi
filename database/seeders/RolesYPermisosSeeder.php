<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

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
        Permission::create(['name' => 'gestionar-roles']);
        Permission::create(['name' => 'gestionar-cuartos']);
        Permission::create(['name' => 'gestionar-solicitud']);
        Permission::create(['name' => 'gestionar-csv']);
        Permission::create(['name' => 'gestionar-reportes']);
        Permission::create(['name' => 'gestionar-estadisticas']);
        Permission::create(['name' => 'registrar-estancia']);
        Permission::create(['name' => 'ver-estancia']);
        Permission::create(['name' => 'aprobar-estancias']);
        Permission::create(['name' => 'ver-parte-diario-departamental']);
        Permission::create(['name' => 'aprobar-parte-diario-departamental']);
        Permission::create(['name' => 'ver-parte-diario-nacional']);
        Permission::create(['name' => 'aprobar-parte-diario-nacional']);

        // Crear Roles y Asignar Permisos
        $roleAdmin = Role::create(['id' => 1, 'name' => 'admin']);
        $roleAdmin->givePermissionTo([
            'gestionar-usuarios',
            'gestionar-roles',
        ]);

        $roleNacional = Role::create(['id' => 2, 'name' => 'Nacional']);
        $roleNacional->givePermissionTo([
            'ver-parte-diario-nacional',
            'aprobar-parte-diario-nacional',
            'gestionar-solicitud',
            'gestionar-reportes',
            'gestionar-estadisticas',
        ]);

        $roleDepartamental = Role::create(['id' => 4, 'name' => 'Departamental']);
        $roleDepartamental->givePermissionTo([
            'ver-parte-diario-departamental',
            'aprobar-parte-diario-departamental',

        ]);

        $rolePrestador = Role::create(['id' => 6, 'name' => 'Prestador']);
        $rolePrestador->givePermissionTo([
            'gestionar-empleados',
            'ver-estancia',
            'aprobar-estancias',
            'gestionar-cuartos',
            'gestionar-csv',
        ]);

        $rolePrestadoremp = Role::create(['id' => 7, 'name' => 'Prestadoremp']);
        $rolePrestadoremp->givePermissionTo([
            'registrar-estancia',
            'ver-estancia',
            'aprobar-estancias',

        ]);

        $roleInstitucion = Role::create(['id' => 8, 'name' => 'Institucion']);
        $roleInstitucion->givePermissionTo('ver-parte-diario-nacional');

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
            'establecimiento_id' => null,
            'sucursal_id' => null,
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
            'establecimiento_id' => null,
            'sucursal_id' => null,
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
            'establecimiento_id' => null,
            'sucursal_id' => null,
        ]);
        $userDepartamental->assignRole($roleDepartamental);

        // 4. Usuario prestador
        $userPrestador = User::create([
            'nombres' => 'prestador1',
            'apellido_paterno' => 'prestador1',
            'apellido_materno' => 'prestador1',
            'ci' => '4567811',
            'celular' => '55566677',
            'email' => 'prestador1@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 7,
            'municipio_id' => 190,
            'establecimiento_id' => 112,
            'sucursal_id' => null,
        ]);
        $userPrestador->assignRole($rolePrestadoremp);

        // 5. Usuario prestadoremp
        $userPrestadoremp = User::create([
            'nombres' => 'prestadoremp1',
            'apellido_paterno' => 'prestadoremp1',
            'apellido_materno' => 'prestadoremp1',
            'ci' => '5611901',
            'celular' => '44455686',
            'email' => 'prestadoremp1@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 5,
            'municipio_id' => 220,
            'establecimiento_id' => 112,
            'sucursal_id' => 1,
        ]);
        $userPrestadoremp->assignRole($rolePrestadoremp);

        // 5. Usuario prestadoremp
        $userPrestadoremp2 = User::create([
            'nombres' => 'prestador2',
            'apellido_paterno' => 'prestadoremp2',
            'apellido_materno' => 'prestadoremp2',
            'ci' => '5012201',
            'celular' => '44411666',
            'email' => 'prestador2@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 5,
            'municipio_id' => 220,
            'establecimiento_id' => 111,
            'sucursal_id' => null,
        ]);
        $userPrestadoremp2->assignRole($rolePrestadoremp);

        // 5. Usuario prestadoremp
        $userPrestadoremp3 = User::create([
            'nombres' => 'prestadoremp2',
            'apellido_paterno' => 'prestadoremp2',
            'apellido_materno' => 'prestadoremp2',
            'ci' => '5811901',
            'celular' => '44459066',
            'email' => 'prestadoremp2@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 5,
            'municipio_id' => 220,
            'establecimiento_id' => 111,
            'sucursal_id' => 2,
        ]);
        $userPrestadoremp3->assignRole($rolePrestadoremp);

                // 6. Usuario Institución
        $userInstitucion = User::create([
            'nombres' => 'institucion',
            'apellido_paterno' => 'institucion',
            'apellido_materno' => 'institucion',
            'ci' => '5608901',
            'celular' => '17455666',
            'email' => 'institucion@example.com',
            'password' => Hash::make('12e45678'),

            'nacionalidad_id' => 24,
            'departamento_id' => 5,
            'municipio_id' => 220,
            'establecimiento_id' => null,
            'sucursal_id' => null,
        ]);
        $userInstitucion->assignRole($roleInstitucion);
    }
}
