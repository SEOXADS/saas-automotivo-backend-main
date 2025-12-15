<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TenantUser;

class AssignRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Atribuir roles aos usuários existentes
        $users = TenantUser::all();

        foreach ($users as $user) {
            if (!$user->hasRole($user->role)) {
                $user->assignRole($user->role);
                $this->command->info("✅ Role '{$user->role}' atribuído ao usuário {$user->name}");
            } else {
                $this->command->info("ℹ️ Usuário {$user->name} já tem o role '{$user->role}'");
            }
        }

        $this->command->info('✅ Roles atribuídos com sucesso!');
    }
}
