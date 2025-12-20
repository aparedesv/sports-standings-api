<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Carregar constants
        require_once app_path('constants.php');

        // Netejar cache de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $entities = PERMISSION_ENTITIES;
        $actions = PERMISSION_ACTIONS;

        // Crear tots els permisos
        $this->command->info('Creant permisos...');
        $permissionsCreated = 0;

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $name = "{$action}_{$entity}";
                Permission::firstOrCreate(['name' => $name]);
                $permissionsCreated++;
            }
        }

        $this->command->info("Creats {$permissionsCreated} permisos.");

        // Carregar configuració de rols
        $rolesConfig = config('roles');

        // Crear rols i assignar permisos
        $this->command->info('Creant rols i assignant permisos...');

        foreach ($rolesConfig as $roleName => $permissionsMap) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Construir llista de permisos
            $permissionsToAssign = [];

            foreach ($permissionsMap as $entity => $entityActions) {
                foreach ($entityActions as $action) {
                    $permissionsToAssign[] = "{$action}_{$entity}";
                }
            }

            // Sincronitzar permisos (afegeix nous, elimina els que no estan a la llista)
            $role->syncPermissions($permissionsToAssign);

            $count = count($permissionsToAssign);
            $this->command->info("  - Rol '{$roleName}': {$count} permisos");
        }

        $this->command->info('Rols i permisos creats correctament!');
    }
}
