<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar se a tabela existe
        if (!Schema::hasTable('system_settings')) {
            $this->command->error('❌ Tabela system_settings não encontrada!');
            return;
        }

        $this->command->info('🌐 Populando configurações do sistema...');

        // Configurações da empresa
        $this->seedCompanySettings();

        // Configurações de idioma
        $this->seedLanguageSettings();

        // Configurações de autenticação
        $this->seedAuthSettings();

        // Configurações de AI
        $this->seedAISettings();

        // Configurações de sistema
        $this->seedSystemSettings();

        // Configurações de SEO
        $this->seedSeoSettings();

        // Configurações de localização
        $this->seedLocationSettings();

        $this->command->info('✅ Configurações do sistema populadas com sucesso!');
    }

    /**
     * Configurações da empresa
     */
    private function seedCompanySettings(): void
    {
        $companySettings = [
            'company_name' => 'Portal Veículos SaaS',
            'company_email' => 'contato@portalveiculos.com',
            'company_phone' => '(11) 99999-9999',
            'company_address' => 'São Paulo, SP - Brasil',
            'company_cnpj' => '00.000.000/0001-00',
            'company_description' => 'Plataforma SaaS completa para gestão de concessionárias e lojas de veículos',
            'company_website' => 'https://portalveiculos.com',
            'company_logo' => null,
        ];

        foreach ($companySettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'company'],
                [
                    'value' => json_encode($value),
                    'updated_by' => 1, // Super admin
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('  ✅ Configurações da empresa');
    }

    /**
     * Configurações de idioma
     */
    private function seedLanguageSettings(): void
    {
        $languageSettings = [
            'default_language' => 'pt_BR',
            'available_languages' => json_encode(['pt_BR', 'en', 'es']),
            'auto_detect' => true,
            'fallback_language' => 'pt_BR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'number_format' => 'pt_BR',
            'currency_format' => 'pt_BR',
        ];

        foreach ($languageSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'language'],
                [
                    'value' => json_encode($value),
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('  ✅ Configurações de idioma');
    }

    /**
     * Configurações de autenticação
     */
    private function seedAuthSettings(): void
    {
        $authSettings = [
            'jwt_expiration' => 3600, // 1 hora
            'jwt_refresh_expiration' => 604800, // 7 dias
            'password_min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => true,
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutos
            'session_timeout' => 1800, // 30 minutos
            'two_factor_enabled' => false,
            'social_login_enabled' => false,
        ];

        foreach ($authSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'auth'],
                [
                    'value' => json_encode($value),
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('  ✅ Configurações de autenticação');
    }

    /**
     * Configurações de AI
     */
    private function seedAISettings(): void
    {
        $aiSettings = [
            'ai_enabled' => true,
            'ai_provider' => 'openai',
            'ai_api_key' => null,
            'ai_model' => 'gpt-3.5-turbo',
            'ai_max_tokens' => 1000,
            'ai_temperature' => 0.7,
            'ai_features' => json_encode([
                'vehicle_description_generation',
                'lead_qualification',
                'price_suggestion',
                'market_analysis'
            ]),
        ];

        foreach ($aiSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'ai'],
                [
                    'value' => json_encode($value),
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('  ✅ Configurações de AI');
    }

    /**
     * Configurações de sistema
     */
    private function seedSystemSettings(): void
    {
        $systemSettings = [
            'maintenance_mode' => false,
            'maintenance_message' => 'Sistema em manutenção. Volte em breve.',
            'debug_mode' => app()->environment('local'),
            'log_level' => 'info',
            'max_file_size' => 10485760, // 10MB
            'allowed_file_types' => json_encode(['jpg', 'jpeg', 'png', 'gif', 'webp']),
            'backup_enabled' => true,
            'backup_frequency' => 'daily',
            'backup_retention' => 30, // dias
            'email_notifications' => true,
            'sms_notifications' => false,
            'push_notifications' => false,
        ];

        foreach ($systemSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'system'],
                [
                    'value' => json_encode($value),
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('  ✅ Configurações de sistema');
    }

    /**
     * Configurações de SEO
     */
    private function seedSeoSettings(): void
    {
        $seoSettings = [
            'meta_title' => 'Portal Veículos SaaS - Gestão Completa para Concessionárias',
            'meta_description' => 'Plataforma SaaS completa para gestão de concessionárias, lojas de veículos e gestão de leads',
            'meta_keywords' => 'veículos, concessionária, gestão, leads, CRM, SaaS, automóveis',
            'google_analytics' => '',
            'facebook_pixel' => '',
            'robots_txt' => 'User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api',
        ];

        foreach ($seoSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'seo'],
                [
                    'value' => json_encode($value),
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('  ✅ Configurações de SEO');
    }

    /**
     * Configurações de localização
     */
    private function seedLocationSettings(): void
    {
        $locationSettings = [
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'currency' => 'BRL',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
        ];

        foreach ($locationSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'location'],
                [
                    'value' => json_encode($value),
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('  ✅ Configurações de localização');
    }
}
