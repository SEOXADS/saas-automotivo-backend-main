# 👨‍💼 Configuração de Usuário Administrador

Este documento descreve como criar usuários administradores no Portal Veículos SaaS.

## 🎯 Opções Disponíveis

### 1. **Comando Artisan (Recomendado)**

Forma mais simples e segura para criar administradores:

```bash
# Modo interativo
php artisan admin:create

# Modo com parâmetros
php artisan admin:create \
  --name="João Silva" \
  --email="admin@empresa.com" \
  --password="senha123" \
  --tenant="empresa" \
  --phone="(11) 99999-9999"
```

**Vantagens:**
- ✅ Validação automática de dados
- ✅ Criação automática de tenant se não existir
- ✅ Interface interativa amigável
- ✅ Verificações de segurança

### 2. **Script de Setup Rápido**

Para configuração inicial rápida:

```bash
./setup-admin.sh
```

Este script guia você pelo processo de criação interativamente.

### 3. **Seeder de Produção**

Para ambiente de produção com variáveis de ambiente:

```bash
# Configurar variáveis no .env
PROD_TENANT_NAME="Minha Empresa"
PROD_TENANT_SUBDOMAIN="empresa"
PROD_TENANT_EMAIL="contato@empresa.com"
PROD_ADMIN_NAME="Administrador"
PROD_ADMIN_EMAIL="admin@empresa.com"
PROD_ADMIN_PASSWORD="senha_super_segura"

# Executar seeder
php artisan db:seed --class=ProductionSeeder
```

### 4. **Via API** (Para sistemas automatizados)

```bash
curl -X POST https://api.exemplo.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin",
    "email": "admin@empresa.com",
    "password": "senha123",
    "password_confirmation": "senha123",
    "tenant_subdomain": "empresa",
    "role": "admin"
  }'
```

## 🔐 Estrutura de Permissões

### Roles Disponíveis:

- **admin**: Administrador completo do tenant
- **manager**: Gerente com permissões limitadas
- **salesperson**: Vendedor
- **user**: Usuário comum

### Permissões do Admin:

```php
[
    'manage_users',      // Gerenciar usuários
    'manage_vehicles',   // Gerenciar veículos
    'manage_leads',      // Gerenciar leads
    'manage_settings',   // Configurações do sistema
    'view_analytics',    // Visualizar relatórios
    'manage_billing',    // Gerenciar cobrança
    'manage_tenants',    // Gerenciar tenants (super admin)
    'system_admin'       // Administração do sistema
]
```

## 🏢 Estrutura de Tenant

Cada admin pertence a um **tenant** (empresa/organização):

```php
Tenant {
    name: "Minha Empresa",
    subdomain: "empresa",
    status: "active",
    plan: "premium",
    features: ["analytics", "crm", "api_access"],
    config: {
        theme_color: "#007bff",
        contact_email: "contato@empresa.com"
    }
}
```

## 📋 Checklist de Configuração

### Para Produção:

- [ ] Configurar variáveis de ambiente
- [ ] Executar migrações: `php artisan migrate`
- [ ] Criar admin: `php artisan admin:create`
- [ ] Configurar SSL/HTTPS
- [ ] Configurar email (SMTP)
- [ ] Configurar armazenamento de arquivos
- [ ] Teste de login e funcionalidades

### Para Desenvolvimento:

- [ ] Executar migrações: `php artisan migrate`
- [ ] Executar seeders: `php artisan db:seed`
- [ ] Acessar com: `admin@demo.com` / `123456`

## 🚨 Segurança

### Senhas Recomendadas:
- Mínimo 8 caracteres
- Combinação de letras, números e símbolos
- Não usar dados pessoais
- Trocar senha padrão imediatamente

### Configurações de Segurança:
```env
# JWT
JWT_TTL=60                 # Token expira em 1 hora
JWT_REFRESH_TTL=20160     # Refresh em 14 dias

# Sessão
SESSION_LIFETIME=120      # Sessão expira em 2 horas
SESSION_SECURE_COOKIE=true # Apenas HTTPS
```

## 📞 Suporte

Em caso de problemas:

1. Verificar logs: `storage/logs/laravel.log`
2. Verificar configuração do banco de dados
3. Verificar permissões de diretório
4. Contatar suporte técnico

## 🔧 Comandos Úteis

```bash
# Listar todos os admins
php artisan tinker
>>> App\Models\TenantUser::where('role', 'admin')->get();

# Resetar senha de admin
php artisan tinker
>>> $user = App\Models\TenantUser::where('email', 'admin@empresa.com')->first();
>>> $user->password = Hash::make('nova_senha');
>>> $user->save();

# Ativar/desativar usuário
>>> $user->is_active = true; // ou false
>>> $user->save();
```

## 📚 Exemplos Práticos

### Criação Básica:
```bash
php artisan admin:create \
  --name="João Silva" \
  --email="joao@empresa.com" \
  --password="senha123" \
  --tenant="empresa"
```

### Criação com Tenant Novo:
```bash
php artisan admin:create \
  --name="Maria Santos" \
  --email="maria@novaempresa.com" \
  --password="senha456" \
  --tenant="novaempresa"
# O comando perguntará se deseja criar o tenant
```

### Via Variáveis de Ambiente:
```bash
export PROD_ADMIN_NAME="Administrador Geral"
export PROD_ADMIN_EMAIL="admin@portal.com"
export PROD_ADMIN_PASSWORD="$(openssl rand -base64 32)"
php artisan db:seed --class=ProductionSeeder
```
