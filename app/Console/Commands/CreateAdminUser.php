<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--name= : Nome do administrador}
                            {--email= : Email do administrador}
                            {--password= : Senha do administrador}
                            {--tenant= : Subdomínio do tenant}
                            {--phone= : Telefone do administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criar um usuário administrador para um tenant específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Criando usuário administrador...');
        $this->newLine();

        // Obter dados via opções ou prompts interativos
        $name = $this->option('name') ?: $this->ask('Nome do administrador');
        $email = $this->option('email') ?: $this->ask('Email do administrador');
        $password = $this->option('password') ?: $this->secret('Senha do administrador');
        $tenantSubdomain = $this->option('tenant') ?: $this->ask('Subdomínio do tenant');
        $phone = $this->option('phone') ?: $this->ask('Telefone (opcional)', null);

        // Validar dados
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'tenant_subdomain' => $tenantSubdomain,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'tenant_subdomain' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Dados inválidos:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  • {$error}");
            }
            return 1;
        }

        // Buscar ou criar tenant
        $tenant = Tenant::where('subdomain', $tenantSubdomain)->first();

        if (!$tenant) {
            if ($this->confirm("Tenant '{$tenantSubdomain}' não existe. Deseja criar?")) {
                $tenant = $this->createTenant($tenantSubdomain);
            } else {
                $this->error('❌ Operação cancelada.');
                return 1;
            }
        }

        // Verificar se email já existe no tenant
        $existingUser = TenantUser::where('email', $email)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($existingUser) {
            $this->error("❌ Email '{$email}' já existe no tenant '{$tenantSubdomain}'");
            return 1;
        }

        // Criar usuário admin
        $admin = TenantUser::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone,
            'role' => 'admin',
            'is_active' => true,
            'permissions' => [
                'manage_users', 'manage_vehicles', 'manage_leads',
                'manage_settings', 'view_analytics', 'manage_billing'
            ],
        ]);

        // Sucesso!
        $this->newLine();
        $this->info('✅ Usuário administrador criado com sucesso!');
        $this->newLine();

        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $admin->id],
                ['Nome', $admin->name],
                ['Email', $admin->email],
                ['Role', $admin->role],
                ['Tenant', $tenant->name . ' (' . $tenant->subdomain . ')'],
                ['Status', $admin->is_active ? 'Ativo' : 'Inativo'],
                ['Criado em', $admin->created_at->format('d/m/Y H:i:s')],
            ]
        );

        $this->newLine();
        $this->info('🔐 Credenciais de acesso:');
        $this->line("📧 Email: {$admin->email}");
        $this->line("🔑 Senha: {$password}");
        $this->line("🏢 Tenant: {$tenant->subdomain}");
        $this->newLine();

        return 0;
    }

    private function createTenant($subdomain)
    {
        $name = $this->ask('Nome da empresa/tenant');
        $email = $this->ask('Email do tenant');
        $phone = $this->ask('Telefone do tenant', '(11) 99999-9999');

        return Tenant::create([
            'name' => $name,
            'subdomain' => $subdomain,
            'email' => $email,
            'phone' => $phone,
            'status' => 'active',
            'plan' => 'premium',
            'trial_ends_at' => now()->addDays(30),
            'subscription_ends_at' => now()->addYear(),
            'features' => ['advanced_filters', 'multiple_users', 'analytics', 'crm'],
            'config' => [
                'theme_color' => '#007bff',
                'logo_url' => null,
                'contact_email' => $email,
                'contact_phone' => $phone,
                'address' => 'Endereço não informado'
            ]
        ]);
    }
}
