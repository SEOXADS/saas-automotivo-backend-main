<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:create-super-admin
                            {--name= : Nome do super administrador}
                            {--email= : Email do super administrador}
                            {--password= : Senha do super administrador}
                            {--phone= : Telefone do super administrador}
                            {--force : Forçar criação mesmo se já existir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criar um super administrador do SaaS que gerencia todos os tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌟 Criando Super Administrador do SaaS Portal Veículos...');
        $this->newLine();

        // Obter dados via opções ou prompts interativos
        $name = $this->option('name') ?: $this->ask('Nome do super administrador');
        $email = $this->option('email') ?: $this->ask('Email do super administrador');
        $password = $this->option('password') ?: $this->secret('Senha do super administrador');
        $phone = $this->option('phone') ?: $this->ask('Telefone (opcional)', null);

        // Validar dados
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Dados inválidos:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  • {$error}");
            }
            return 1;
        }

        // Verificar se email já existe
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && !$this->option('force')) {
            $this->error("❌ Email '{$email}' já existe no sistema!");

            if ($this->confirm('Deseja atualizar este usuário para super admin?')) {
                return $this->updateExistingUser($existingUser, $name, $password, $phone);
            }

            return 1;
        }

        // Criar ou atualizar super admin
        if ($existingUser && $this->option('force')) {
            return $this->updateExistingUser($existingUser, $name, $password, $phone);
        }

        // Criar novo super admin
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
                ]
            ],
        ]);

        // Sucesso!
        $this->newLine();
        $this->info('✅ Super Administrador criado com sucesso!');
        $this->newLine();

        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $superAdmin->id],
                ['Nome', $superAdmin->name],
                ['Email', $superAdmin->email],
                ['Role', 'Super Admin'],
                ['Permissões', count($superAdmin->permissions) . ' permissões'],
                ['Status', $superAdmin->is_active ? 'Ativo' : 'Inativo'],
                ['Criado em', $superAdmin->created_at->format('d/m/Y H:i:s')],
            ]
        );

        $this->newLine();
        $this->info('🔐 CREDENCIAIS DE ACESSO:');
        $this->line("📧 Email: {$superAdmin->email}");
        $this->line("🔑 Senha: {$password}");
        $this->line("🌐 URL Admin: " . config('app.url') . '/admin');
        $this->newLine();

        $this->info('🌟 PERMISSÕES DO SUPER ADMIN:');
        foreach (User::getSuperAdminPermissions() as $permission) {
            $this->line("  ✓ " . str_replace('_', ' ', ucfirst($permission)));
        }

        $this->newLine();
        $this->warn('⚠️  IMPORTANTE: Salve essas credenciais em local seguro!');
        $this->warn('⚠️  O Super Admin tem acesso TOTAL ao sistema!');

        return 0;
    }

    private function updateExistingUser($user, $name, $password, $phone)
    {
        $user->update([
            'name' => $name,
            'password' => Hash::make($password),
            'phone' => $phone,
            'role' => 'super_admin',
            'is_active' => true,
            'permissions' => User::getSuperAdminPermissions(),
            'email_verified_at' => now(),
        ]);

        $this->info('✅ Usuário existente atualizado para Super Admin!');
        $this->newLine();

        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $user->id],
                ['Nome', $user->name],
                ['Email', $user->email],
                ['Role', 'Super Admin (Atualizado)'],
                ['Atualizado em', now()->format('d/m/Y H:i:s')],
            ]
        );

        return 0;
    }
}
