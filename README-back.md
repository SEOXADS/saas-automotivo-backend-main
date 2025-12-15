# 🚗 Portal Veículos SaaS - Backend

Sistema SaaS completo para criação de portais de veículos com painel administrativo multi-tenant.

## 🚀 Funcionalidades

### ✅ Sistema Multi-tenancy
- Isolamento completo de dados por cliente
- Suporte a subdomínios
- Planos diferenciados (basic, premium, enterprise)
- Gestão de recursos por plano

### 🔐 Autenticação JWT
- Login/logout seguros
- Renovação automática de tokens
- Roles e permissões por tenant
- Middleware de proteção

### 🚙 Gestão de Veículos
- CRUD completo de veículos
- Sistema de imagens múltiplas
- Filtros avançados (marca, modelo, preço, ano, etc.)
- Características personalizáveis
- Status de disponibilidade

### 📊 CRM de Leads
- Captura automática de leads
- Pipeline de vendas
- Atribuição de vendedores
- Dashboard analítico
- Tracking de origem

### 📚 Documentação API
- Swagger/OpenAPI integrado
- Documentação automática
- Teste direto das rotas
- Exemplos de uso

## 🛠️ Instalação

### 1. Clonar e instalar dependências
```bash
cd backend
composer install
```

### 2. Configurar banco de dados
```bash
# Criar banco no MySQL
CREATE DATABASE portal_veiculos_saas;

# Configurar .env
cp .env.example .env
```

### 3. Configurar variáveis de ambiente
```env
APP_NAME="Portal Veículos SaaS"
APP_URL=http://localhost:8000
APP_LOCALE=pt

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portal_veiculos_saas
DB_USERNAME=root
DB_PASSWORD=root

JWT_SECRET=your-jwt-secret-key
JWT_TTL=60
```

### 4. Executar migrations
```bash
php artisan migrate
```

### 5. Gerar documentação Swagger
```bash
php artisan l5-swagger:generate
```

### 6. Iniciar servidor
```bash
php artisan serve
```

## 📖 Documentação API

Acesse a documentação Swagger em: `http://localhost:8000/api/documentation`

## 🔗 Principais Endpoints

### Autenticação
```
POST /api/auth/login
POST /api/auth/register
POST /api/auth/logout
GET  /api/auth/me
POST /api/auth/refresh
```

### Veículos
```
GET    /api/vehicles
POST   /api/vehicles
GET    /api/vehicles/{id}
PUT    /api/vehicles/{id}
DELETE /api/vehicles/{id}
GET    /api/vehicles/filters
```

### Leads
```
GET    /api/leads
POST   /api/leads
GET    /api/leads/{id}
PUT    /api/leads/{id}
DELETE /api/leads/{id}
GET    /api/leads/dashboard
POST   /api/leads/{id}/status
POST   /api/leads/{id}/assign
```

### Imagens
```
GET    /api/vehicles/{vehicle_id}/images
POST   /api/vehicles/{vehicle_id}/images
DELETE /api/vehicles/{vehicle_id}/images/{id}
POST   /api/vehicles/{vehicle_id}/images/{id}/primary
```

### Rotas Públicas
```
GET  /api/public/vehicles
GET  /api/public/vehicles/{id}
POST /api/public/leads
GET  /api/public/filters
```

## 🏢 Estrutura Multi-tenant

### Como funciona:
1. **Header obrigatório**: `X-Tenant-Subdomain: cliente1`
2. **Dados isolados**: Cada tenant tem seus próprios dados
3. **Usuários separados**: Cada tenant tem seus usuários
4. **Permissões**: Roles específicos por tenant

### Exemplo de login:
```json
{
  "email": "admin@cliente1.com",
  "password": "senha123",
  "tenant_subdomain": "cliente1"
}
```

## 🔐 Autenticação

### 1. Fazer login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@cliente1.com",
    "password": "senha123",
    "tenant_subdomain": "cliente1"
  }'
```

### 2. Usar token nas requisições
```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -H "X-Tenant-Subdomain: cliente1" \
     http://localhost:8000/api/vehicles
```

## 🗃️ Estrutura do Banco

### Tabelas principais:
- `tenants` - Clientes SaaS
- `tenant_users` - Usuários dos tenants
- `vehicles` - Veículos
- `vehicle_brands` - Marcas
- `vehicle_models` - Modelos
- `vehicle_images` - Imagens
- `vehicle_features` - Características
- `leads` - Leads do CRM

## 📊 Dashboard

### Métricas disponíveis:
- Total de veículos
- Veículos ativos
- Total de leads
- Leads novos
- Taxa de conversão
- Leads por origem

### Acesso:
```
GET /api/dashboard
GET /api/leads/dashboard
```

## 🔧 Configurações Avançadas

### Configurar CORS (se necessário):
```php
// config/cors.php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],
'allowed_headers' => ['*'],
```

### Configurar storage para imagens:
```bash
php artisan storage:link
```

## 🐛 Solução de Problemas

### JWT não funciona:
```bash
php artisan jwt:secret
```

### Swagger não gera:
```bash
php artisan l5-swagger:generate
```

### Erro de permissão:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## 🎯 Próximos Passos

1. **Frontend Next.js**: Interface administrativa
2. **Site público**: Catálogo de veículos
3. **Integração WhatsApp**: Automação de mensagens
4. **Relatórios avançados**: Analytics detalhados
5. **Integração FIPE**: Preços automáticos

## 📝 Licença

MIT License - veja o arquivo LICENSE para mais detalhes.

---

**Desenvolvido com ❤️ para revolucionar o mercado de veículos!**
