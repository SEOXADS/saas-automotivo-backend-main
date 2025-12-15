<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configurações via environment
        $name = env('SUPER_ADMIN_NAME', 'Super Administrador');
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@portal.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'Portal@2025!');
        $phone = env('SUPER_ADMIN_PHONE', '(11) 99999-0000');

        // Verificar se super admin já existe
        $existingSuperAdmin = User::where('email', $email)->first();

        if (!$existingSuperAdmin) {
            $superAdmin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'phone' => $phone,
                'role' => 'super_admin',
                'is_active' => true,
                'permissions' => User::getSuperAdminPermissions(),
                'email_verified_at' => now(),
                'settings' => [
                    'theme' => 'dark',
                    'language' => 'pt',
                    'timezone' => 'America/Sao_Paulo',
                    'notifications' => [
                        'email' => true,
                        'system' => true,
                        'tenant_alerts' => true,
                        'security_alerts' => true,
                    ],
                    'dashboard' => [
                        'show_stats' => true,
                        'show_recent_tenants' => true,
                        'show_system_health' => true,
                    ]
                ],
            ]);

            $this->command->info('✅ Super Administrador criado com sucesso!');
            $this->command->newLine();
            $this->command->info('🔐 CREDENCIAIS DE SUPER ADMIN:');
            $this->command->info("📧 Email: {$email}");
            $this->command->info("🔑 Senha: {$password}");
            $this->command->info("🌟 Role: Super Admin");
            $this->command->info("🔧 Permissões: " . count($superAdmin->permissions));
            $this->command->newLine();
            $this->command->warn('⚠️  IMPORTANTE: Altere a senha padrão em produção!');
        } else {
            $this->command->info("ℹ️  Super Admin '{$email}' já existe.");

            // Atualizar permissões se necessário
            if ($existingSuperAdmin->role === 'super_admin') {
                $existingSuperAdmin->update([
                    'permissions' => User::getSuperAdminPermissions(),
                ]);
                $this->command->info('✅ Permissões do Super Admin atualizadas.');
            }
        }

        // Criar admin de suporte se não existir
        $supportEmail = env('SUPPORT_ADMIN_EMAIL', 'suporte@portal.com');
        $existingSupport = User::where('email', $supportEmail)->first();

        if (!$existingSupport) {
            $supportAdmin = User::create([
                'name' => 'Administrador de Suporte',
                'email' => $supportEmail,
                'password' => Hash::make(env('SUPPORT_ADMIN_PASSWORD', 'Suporte@2025!')),
                'phone' => '(11) 99999-0001',
                'role' => 'support',
                'is_active' => true,
                'permissions' => [
                    'view_system_analytics',
                    'manage_tenant_users',
                    'view_logs',
                    'manage_billing',
                ],
                'email_verified_at' => now(),
                'settings' => [
                    'theme' => 'light',
                    'language' => 'pt',
                    'timezone' => 'America/Sao_Paulo',
                ]
            ]);

            $this->command->info('✅ Administrador de Suporte criado!');
            $this->command->info("📧 Email: {$supportEmail}");
        }
    }
}
