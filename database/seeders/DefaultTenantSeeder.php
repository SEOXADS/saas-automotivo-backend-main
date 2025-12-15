<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DefaultTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primeiro, remover a marcação de padrão de todos os tenants
        Tenant::query()->update(['is_default' => false]);

        // Verificar se existe um tenant 'demo'
        $demoTenant = Tenant::where('subdomain', 'demo')->first();

        if ($demoTenant) {
            // Definir o tenant 'demo' como padrão
            $demoTenant->update(['is_default' => true]);
            $this->command->info('Tenant "demo" definido como padrão.');
        } else {
            // Se não existir, criar um tenant padrão
            $defaultTenant = Tenant::create([
                'name' => 'Portal Demo',
                'subdomain' => 'demo',
                'email' => 'contato@demo.com',
                'phone' => '(11) 99999-9999',
                'status' => 'active',
                'plan' => 'premium',
                'is_default' => true,
                'config' => [
                    'timezone' => 'America/Sao_Paulo',
                    'locale' => 'pt_BR',
                    'currency' => 'BRL'
                ]
            ]);

            $this->command->info('Tenant padrão "demo" criado com sucesso.');
        }

        // Verificar se existe pelo menos um tenant padrão
        $defaultTenant = Tenant::where('is_default', true)->first();

        if (!$defaultTenant) {
            // Se não houver nenhum padrão, pegar o primeiro tenant ativo
            $firstActiveTenant = Tenant::where('status', 'active')->first();

            if ($firstActiveTenant) {
                $firstActiveTenant->update(['is_default' => true]);
                $this->command->info("Tenant '{$firstActiveTenant->subdomain}' definido como padrão.");
            } else {
                $this->command->error('Nenhum tenant ativo encontrado para definir como padrão.');
            }
        }

        $this->command->info('Seeder DefaultTenantSeeder executado com sucesso.');
    }
}
