<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar cache de permissões
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Criar permissões básicas
        $permissions = [
            // Veículos
            'view-vehicles',
            'create-vehicles',
            'edit-vehicles',
            'delete-vehicles',

            // Marcas
            'view-brands',
            'create-brands',
            'edit-brands',
            'delete-brands',

            // Modelos
            'view-models',
            'create-models',
            'edit-models',
            'delete-models',

            // Leads
            'view-leads',
            'create-leads',
            'edit-leads',
            'delete-leads',
            'assign-leads',

            // Usuários do tenant
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'activate-users',
            'deactivate-users',

            // Configurações
            'view-settings',
            'edit-settings',

            // Configurações do Tenant (geral, site, auth, plugins, etc.)
            'manage-tenant-settings',

            // Mensagens do Sistema
            'manage-system-messages',

            // Gerenciar tenants (apenas super admin)
            'manage-tenants',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Criar roles e atribuir permissões
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $salespersonRole = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'api']);

        $adminRole->givePermissionTo($permissions);

        $managerRole->givePermissionTo([
            'view-vehicles', 'create-vehicles', 'edit-vehicles',
            'view-brands', 'view-models',
            'view-leads', 'create-leads', 'edit-leads', 'assign-leads',
            'view-users',
            'view-settings',
        ]);

        $salespersonRole->givePermissionTo([
            'view-vehicles',
            'view-brands', 'view-models',
            'view-leads', 'create-leads', 'edit-leads',
        ]);

        $this->command->info('✅ Permissões e roles criados com sucesso!');
    }
}
