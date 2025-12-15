<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\TenantUser;

class ListTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:list
                            {--detailed : Mostrar informações detalhadas}
                            {--status= : Filtrar por status (active, inactive, suspended)}
                            {--plan= : Filtrar por plano (basic, premium, enterprise)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listar todos os tenants do sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📋 Listando todos os tenants...');
        $this->newLine();

        // Construir query base
        $query = Tenant::with(['profile', 'theme', 'seo', 'portalSettings', 'users']);

        // Aplicar filtros
        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        if ($plan = $this->option('plan')) {
            $query->where('plan', $plan);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('Nenhum tenant encontrado.');
            return 0;
        }

        if ($this->option('detailed')) {
            $this->displayDetailedList($tenants);
        } else {
            $this->displaySimpleList($tenants);
        }

        $this->newLine();
        $this->info("Total de tenants: {$tenants->count()}");

        return 0;
    }

    /**
     * Exibir lista simples
     */
    private function displaySimpleList($tenants): void
    {
        $headers = ['ID', 'Nome', 'Subdomínio', 'Status', 'Plano', 'Admin', 'Configurações'];

        $rows = $tenants->map(function ($tenant) {
            $admin = $tenant->users()->where('role', 'admin')->first();
            $adminName = $admin ? $admin->name : 'N/A';

            $configs = [];
            if ($tenant->profile) $configs[] = 'Profile';
            if ($tenant->theme) $configs[] = 'Theme';
            if ($tenant->seo) $configs[] = 'SEO';
            if ($tenant->portalSettings) $configs[] = 'Portal';

            return [
                $tenant->id,
                $tenant->name,
                $tenant->subdomain,
                $tenant->status,
                $tenant->plan,
                $adminName,
                implode(', ', $configs)
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    /**
     * Exibir lista detalhada
     */
    private function displayDetailedList($tenants): void
    {
        foreach ($tenants as $tenant) {
            $this->info("🏢 TENANT: {$tenant->name} (ID: {$tenant->id})");
            $this->line("=" . str_repeat('=', strlen($tenant->name) + 20));

            // Informações básicas
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $tenant->id],
                    ['Nome', $tenant->name],
                    ['Subdomínio', $tenant->subdomain],
                    ['Email', $tenant->email],
                    ['Telefone', $tenant->phone],
                    ['Status', $tenant->status],
                    ['Plano', $tenant->plan],
                    ['Trial até', $tenant->trial_ends_at ? $tenant->trial_ends_at->format('d/m/Y') : 'N/A'],
                    ['Assinatura até', $tenant->subscription_ends_at ? $tenant->subscription_ends_at->format('d/m/Y') : 'N/A'],
                    ['Features', implode(', ', $tenant->features ?? [])],
                    ['Criado em', $tenant->created_at->format('d/m/Y H:i:s')],
                    ['Atualizado em', $tenant->updated_at->format('d/m/Y H:i:s')],
                ]
            );

            // Configurações relacionadas
            $this->info('📊 CONFIGURAÇÕES:');

            if ($tenant->profile) {
                $this->line("✅ Perfil: {$tenant->profile->company_name}");
                $this->line("   Descrição: {$tenant->profile->company_description}");
                $this->line("   CNPJ: " . ($tenant->profile->company_cnpj ?: 'N/A'));
                $this->line("   Website: " . ($tenant->profile->company_website ?: 'N/A'));
            } else {
                $this->line("❌ Perfil: Não configurado");
            }

            if ($tenant->theme) {
                $this->line("✅ Tema: {$tenant->theme->theme_name} v{$tenant->theme->theme_version}");
                $this->line("   Cor principal: {$tenant->theme->primary_color}");
                $this->line("   Fonte: {$tenant->theme->font_family}");
            } else {
                $this->line("❌ Tema: Não configurado");
            }

            if ($tenant->seo) {
                $this->line("✅ SEO: {$tenant->seo->meta_title}");
                $this->line("   Meta descrição: {$tenant->seo->meta_description}");
                $this->line("   Meta keywords: {$tenant->seo->meta_keywords}");
            } else {
                $this->line("❌ SEO: Não configurado");
            }

            if ($tenant->portalSettings) {
                $this->line("✅ Portal: Busca " . ($tenant->portalSettings->enable_search ? 'Ativada' : 'Desativada'));
                $this->line("   Filtros: " . ($tenant->portalSettings->enable_filters ? 'Ativados' : 'Desativados'));
                $this->line("   Veículos por página: {$tenant->portalSettings->vehicles_per_page}");
            } else {
                $this->line("❌ Portal: Não configurado");
            }

            // Usuários
            $this->info('👥 USUÁRIOS:');
            $users = $tenant->users;
            if ($users->isNotEmpty()) {
                $userRows = $users->map(function ($user) {
                    return [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->role,
                        $user->is_active ? 'Ativo' : 'Inativo',
                        $user->created_at->format('d/m/Y')
                    ];
                })->toArray();

                $this->table(
                    ['ID', 'Nome', 'Email', 'Role', 'Status', 'Criado em'],
                    $userRows
                );
            } else {
                $this->line("❌ Nenhum usuário encontrado");
            }

            $this->newLine();
        }
    }
}
