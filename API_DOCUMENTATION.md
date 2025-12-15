# 📚 Documentação da API - SaaS Automotivo

## 🚀 Visão Geral

Esta documentação descreve todos os endpoints da API do sistema SaaS Automotivo, organizados por funcionalidade e tipo de usuário.

**Base URL:** `/api`

**Autenticação:** JWT Token (Bearer Token)

---

## 🔐 Autenticação

### Super Admin
- **Guard:** `super_admin`
- **Middleware:** `auth:super_admin`

### Tenant User (Admin Client)
- **Guard:** `tenant`
- **Middleware:** `auth:tenant` + `tenant.identification`

---

## 👑 Super Admin

### 🔑 Autenticação do Super Admin

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `POST` | `/super-admin/login` | Login do super admin | ❌ |
| `POST` | `/super-admin/logout` | Logout do super admin | ✅ |
| `POST` | `/super-admin/refresh` | Renovar token | ✅ |
| `GET` | `/super-admin/me` | Informações do usuário logado | ✅ |
| `POST` | `/super-admin/forgot-password` | Recuperar senha | ❌ |
| `POST` | `/super-admin/reset-password` | Redefinir senha | ❌ |

### 👤 Perfil do Super Admin

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/profile` | Exibir perfil | ✅ |
| `PUT` | `/super-admin/profile` | Atualizar perfil | ✅ |
| `PUT` | `/super-admin/profile/password` | Alterar senha | ✅ |
| `PUT` | `/super-admin/profile/avatar` | Upload de avatar | ✅ |
| `DELETE` | `/super-admin/profile/avatar` | Remover avatar | ✅ |
| `GET` | `/super-admin/profile/activity` | Histórico de atividades | ✅ |
| `GET` | `/super-admin/profile/sessions` | Sessões ativas | ✅ |
| `POST` | `/super-admin/profile/sessions/{sessionId}/revoke` | Revogar sessão | ✅ |
| `POST` | `/super-admin/profile/sessions/revoke-all` | Revogar todas as sessões | ✅ |
| `GET` | `/super-admin/profile/preferences` | Obter preferências | ✅ |
| `PUT` | `/super-admin/profile/preferences` | Atualizar preferências | ✅ |

### 🏢 Gerenciamento de Tenants

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/tenants` | Listar todos os tenants | ✅ |
| `POST` | `/super-admin/tenants` | Criar novo tenant | ✅ |
| `GET` | `/super-admin/tenants/{id}` | Exibir detalhes do tenant | ✅ |
| `PUT` | `/super-admin/tenants/{id}` | Atualizar tenant | ✅ |
| `DELETE` | `/super-admin/tenants/{id}` | Deletar tenant | ✅ |
| `POST` | `/super-admin/tenants/{id}/activate` | Ativar tenant | ✅ |
| `POST` | `/super-admin/tenants/{id}/deactivate` | Desativar tenant | ✅ |
| `GET` | `/super-admin/tenants/{id}/stats` | Estatísticas do tenant | ✅ |

### 👥 Usuários dos Tenants

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/tenants/{tenantId}/users` | Listar usuários do tenant | ✅ |
| `POST` | `/super-admin/tenants/{tenantId}/users` | Criar usuário no tenant | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/users/{userId}` | Exibir usuário específico | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/users/{userId}` | Atualizar usuário | ✅ |
| `DELETE` | `/super-admin/tenants/{tenantId}/users/{userId}` | Deletar usuário | ✅ |
| `POST` | `/super-admin/tenants/{tenantId}/users/{userId}/activate` | Ativar usuário | ✅ |
| `POST` | `/super-admin/tenants/{tenantId}/users/{userId}/deactivate` | Desativar usuário | ✅ |

### 🚗 Veículos dos Tenants

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/tenants/{tenantId}/vehicles` | Listar veículos do tenant | ✅ |
| `POST` | `/super-admin/tenants/{tenantId}/vehicles` | Criar veículo no tenant | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/vehicles/{vehicleId}` | Exibir veículo específico | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/vehicles/{vehicleId}` | Atualizar veículo | ✅ |
| `DELETE` | `/super-admin/tenants/{tenantId}/vehicles/{vehicleId}` | Deletar veículo | ✅ |
| `POST` | `/super-admin/tenants/{tenantId}/vehicles/{vehicleId}/activate` | Ativar veículo | ✅ |
| `POST` | `/super-admin/tenants/{tenantId}/vehicles/{vehicleId}/deactivate` | Desativar veículo | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/vehicles/stats` | Estatísticas dos veículos | ✅ |

### ⚙️ Configurações dos Tenants

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/tenants/{tenantId}/config` | Exibir configurações | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config` | Atualizar configurações | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/config/theme` | Obter tema | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config/theme` | Atualizar tema | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/config/social-media` | Obter redes sociais | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config/social-media` | Atualizar redes sociais | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/config/business-hours` | Obter horários | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config/business-hours` | Atualizar horários | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/config/contact` | Obter contato | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config/contact` | Atualizar contato | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/config/portal` | Obter portal | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config/portal` | Atualizar portal | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/config/seo` | Obter SEO | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config/seo` | Atualizar SEO | ✅ |
| `GET` | `/super-admin/tenants/{tenantId}/config/ai` | Obter IA | ✅ |
| `PUT` | `/super-admin/tenants/{tenantId}/config/ai` | Atualizar IA | ✅ |

### 📊 Dashboard do Super Admin

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/dashboard` | Dashboard principal | ✅ |

### ⚙️ Configurações do Sistema

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/settings/general` | Configurações gerais | ✅ |
| `POST` | `/super-admin/settings/general` | Salvar configurações gerais | ✅ |
| `GET` | `/super-admin/settings/security` | Configurações de segurança | ✅ |
| `POST` | `/super-admin/settings/security` | Salvar configurações de segurança | ✅ |
| `GET` | `/super-admin/settings/database` | Configurações do banco | ✅ |
| `POST` | `/super-admin/settings/database` | Salvar configurações do banco | ✅ |
| `GET` | `/super-admin/settings/notifications` | Configurações de notificações | ✅ |
| `POST` | `/super-admin/settings/notifications` | Salvar configurações de notificações | ✅ |

### 🌐 Configurações do Site

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/site-config/company` | Configurações da empresa | ✅ |
| `POST` | `/super-admin/site-config/company` | Atualizar empresa | ✅ |
| `GET` | `/super-admin/site-config/location` | Configurações de localização | ✅ |
| `POST` | `/super-admin/site-config/location` | Atualizar localização | ✅ |
| `GET` | `/super-admin/site-config/seo` | Configurações de SEO | ✅ |
| `POST` | `/super-admin/site-config/seo` | Atualizar SEO | ✅ |
| `GET` | `/super-admin/site-config/ai` | Configurações de IA | ✅ |
| `POST` | `/super-admin/site-config/ai` | Atualizar IA | ✅ |
| `GET` | `/super-admin/site-config/maintenance` | Status de manutenção | ✅ |
| `POST` | `/super-admin/site-config/maintenance` | Atualizar manutenção | ✅ |
| `GET` | `/super-admin/site-config/all` | Todas as configurações | ✅ |

### 🔐 Configurações de Autenticação

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/site-config/auth` | Configurações de auth | ✅ |
| `POST` | `/super-admin/site-config/auth` | Atualizar auth | ✅ |
| `GET` | `/super-admin/site-config/auth/oauth` | Configurações OAuth | ✅ |
| `POST` | `/super-admin/site-config/auth/oauth` | Atualizar OAuth | ✅ |

### 🔌 Gerenciamento de Plugins

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/site-config/plugins` | Listar plugins | ✅ |
| `POST` | `/super-admin/site-config/plugins/{pluginId}/toggle` | Ativar/desativar plugin | ✅ |
| `GET` | `/super-admin/site-config/plugins/{pluginId}/settings` | Configurações do plugin | ✅ |
| `POST` | `/super-admin/site-config/plugins/{pluginId}/settings` | Atualizar plugin | ✅ |

### 🌍 Configurações de Idioma

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/site-config/languages` | Configurações de idiomas | ✅ |
| `POST` | `/super-admin/site-config/languages` | Atualizar idiomas | ✅ |
| `GET` | `/super-admin/site-config/languages/available` | Idiomas disponíveis | ✅ |
| `GET` | `/super-admin/site-config/languages/translations` | Arquivos de tradução | ✅ |
| `POST` | `/super-admin/site-config/languages/translations/export` | Exportar traduções | ✅ |
| `POST` | `/super-admin/site-config/languages/translations/import` | Importar traduções | ✅ |

### 🔧 Outras Configurações

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/super-admin/other-config/sitemap` | Gerar sitemap | ✅ |
| `POST` | `/super-admin/other-config/clear-cache` | Limpar cache | ✅ |
| `GET` | `/super-admin/other-config/storage-info` | Informações de armazenamento | ✅ |
| `POST` | `/super-admin/other-config/storage-cleanup` | Limpeza de armazenamento | ✅ |
| `GET` | `/super-admin/other-config/cronjobs` | Listar cronjobs | ✅ |
| `POST` | `/super-admin/other-config/cronjobs/{command}/run` | Executar cronjob | ✅ |
| `POST` | `/super-admin/other-config/backup/system` | Backup do sistema | ✅ |
| `POST` | `/super-admin/other-config/backup/database` | Backup do banco | ✅ |
| `GET` | `/super-admin/other-config/system-update/check` | Verificar atualizações | ✅ |
| `POST` | `/super-admin/other-config/system-update/install` | Instalar atualizações | ✅ |

---

## 🏢 Tenant User (Admin Client)

### 🔑 Autenticação do Tenant User

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `POST` | `/auth/login` | Login do usuário | ❌ |
| `POST` | `/auth/register` | Registro de usuário | ❌ |

### 👤 Perfil do Tenant User

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/profile` | Exibir perfil | ✅ |
| `PUT` | `/profile` | Atualizar perfil | ✅ |
| `PUT` | `/profile/password` | Alterar senha | ✅ |
| `PUT` | `/profile/avatar` | Upload de avatar | ✅ |
| `DELETE` | `/profile/avatar` | Remover avatar | ✅ |
| `GET` | `/profile/activity` | Histórico de atividades | ✅ |
| `GET` | `/profile/sessions` | Sessões ativas | ✅ |
| `POST` | `/profile/sessions/{sessionId}/revoke` | Revogar sessão | ✅ |
| `POST` | `/profile/sessions/revoke-all` | Revogar todas as sessões | ✅ |
| `GET` | `/profile/preferences` | Obter preferências | ✅ |
| `PUT` | `/profile/preferences` | Atualizar preferências | ✅ |
| `GET` | `/profile/notifications` | Configurações de notificações | ✅ |
| `PUT` | `/profile/notifications` | Atualizar notificações | ✅ |
| `GET` | `/profile/security` | Configurações de segurança | ✅ |
| `PUT` | `/profile/security` | Atualizar segurança | ✅ |

### 📊 Analytics

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/analytics/dashboard` | Dashboard de analytics | ✅ |
| `GET` | `/analytics/page-views` | Visualizações de página | ✅ |
| `GET` | `/analytics/leads` | Estatísticas de leads | ✅ |
| `GET` | `/analytics/search` | Estatísticas de busca | ✅ |

### ⚙️ Configurações do Site

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/site-config` | Obter configurações | ✅ |
| `PUT` | `/site-config` | Atualizar configurações básicas | ✅ |
| `PUT` | `/site-config/theme` | Atualizar tema | ✅ |
| `POST` | `/site-config/logo` | Upload de logo | ✅ |
| `PUT` | `/site-config/social-media` | Atualizar redes sociais | ✅ |
| `PUT` | `/site-config/business-hours` | Atualizar horários | ✅ |
| `PUT` | `/site-config/portal-settings` | Atualizar portal | ✅ |

---

## 🌐 Portal Público

### 📱 Portal de Anúncios

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/portal/vehicles` | Listar veículos | ❌ |
| `GET` | `/portal/vehicles/{id}` | Exibir veículo | ❌ |
| `GET` | `/portal/filters` | Obter filtros | ❌ |
| `POST` | `/portal/leads` | Criar lead | ❌ |
| `GET` | `/portal/tenant-info` | Informações do tenant | ❌ |

### 🎨 Configurações do Tenant (Público)

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/portal/tenant/theme` | Obter tema | ❌ |
| `GET` | `/portal/tenant/social-media` | Obter redes sociais | ❌ |
| `GET` | `/portal/tenant/business-hours` | Obter horários | ❌ |
| `GET` | `/portal/tenant/contact` | Obter contato | ❌ |
| `GET` | `/portal/tenant/portal-config` | Obter configurações do portal | ❌ |

### 🖼️ Imagens Públicas

| Método | Endpoint | Descrição | Autenticação |
|--------|----------|-----------|--------------|
| `GET` | `/public/images/{tenantId}/{vehicleId}/{filename}` | Servir imagem | ❌ |
| `GET` | `/public/images/{tenantId}/{vehicleId}/{filename}/url` | Obter URL da imagem | ❌ |

---

## 📋 Detalhamento dos Endpoints

### 🔐 Autenticação

#### Login do Super Admin
```http
POST /api/super-admin/login
Content-Type: application/json

{
    "email": "admin@saas.com",
    "password": "senha123"
}
```

**Resposta:**
```json
{
    "success": true,
    "message": "Login realizado com sucesso",
    "data": {
        "user": {
            "id": 1,
            "name": "Super Admin",
            "email": "admin@saas.com",
            "role": "super_admin"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600
    }
}
```

#### Login do Tenant User
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@tenant.com",
    "password": "senha123",
    "tenant_subdomain": "meutenant"
}
```

### 👤 Perfil

#### Atualizar Perfil
```http
PUT /api/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Novo Nome",
    "phone": "+55 11 99999-9999"
}
```

#### Alterar Senha
```http
PUT /api/profile/password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "senha123",
    "new_password": "novaSenha123",
    "new_password_confirmation": "novaSenha123"
}
```

#### Upload de Avatar
```http
PUT /api/profile/avatar
Authorization: Bearer {token}
Content-Type: multipart/form-data

avatar: [arquivo de imagem]
```

### 🏢 Tenants

#### Criar Tenant
```http
POST /api/super-admin/tenants
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Meu Tenant",
    "subdomain": "meutenant",
    "email": "admin@meutenant.com",
    "phone": "+55 11 99999-9999",
    "address": "Rua Exemplo, 123",
    "city": "São Paulo",
    "state": "SP",
    "country": "Brasil"
}
```

#### Ativar/Desativar Tenant
```http
POST /api/super-admin/tenants/{id}/activate
Authorization: Bearer {token}
```

### 🚗 Veículos

#### Criar Veículo
```http
POST /api/super-admin/tenants/{tenantId}/vehicles
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Carro em Excelente Estado",
    "description": "Veículo bem conservado, único dono",
    "brand_id": 1,
    "model_id": 5,
    "year": 2020,
    "fuel_type": "flex",
    "transmission": "automatica",
    "mileage": 45000,
    "color": "Branco",
    "price": 45000.00
}
```

#### Listar Veículos com Filtros
```http
GET /api/super-admin/tenants/{tenantId}/vehicles?status=active&brand_id=1&year=2020&fuel_type=flex&search=carro&per_page=20
Authorization: Bearer {token}
```

### ⚙️ Configurações

#### Atualizar Tema
```http
PUT /api/super-admin/tenants/{tenantId}/config/theme
Authorization: Bearer {token}
Content-Type: application/json

{
    "primary_color": "#007bff",
    "secondary_color": "#6c757d",
    "accent_color": "#28a745",
    "font_family": "Inter, sans-serif",
    "font_size": "medium",
    "border_radius": "medium",
    "button_style": "rounded",
    "layout_style": "modern"
}
```

#### Atualizar Redes Sociais
```http
PUT /api/super-admin/tenants/{tenantId}/config/social-media
Authorization: Bearer {token}
Content-Type: application/json

{
    "facebook": "https://facebook.com/meutenant",
    "instagram": "https://instagram.com/meutenant",
    "whatsapp": "+55 11 99999-9999"
}
```

#### Atualizar Horários de Funcionamento
```http
PUT /api/super-admin/tenants/{tenantId}/config/business-hours
Authorization: Bearer {token}
Content-Type: application/json

{
    "monday": {
        "open": "08:00",
        "close": "18:00",
        "closed": false
    },
    "tuesday": {
        "open": "08:00",
        "close": "18:00",
        "closed": false
    },
    "sunday": {
        "closed": true
    }
}
```

### 📊 Analytics

#### Dashboard de Analytics
```http
GET /api/analytics/dashboard
Authorization: Bearer {token}
```

**Resposta:**
```json
{
    "success": true,
    "data": {
        "total_vehicles": 150,
        "active_vehicles": 120,
        "total_leads": 45,
        "conversion_rate": 0.3,
        "page_views": {
            "today": 1250,
            "week": 8750,
            "month": 35000
        },
        "top_vehicles": [...],
        "recent_leads": [...]
    }
}
```

---

## 🔒 Segurança

### Headers Obrigatórios

#### Para Super Admin
```http
Authorization: Bearer {jwt_token}
```

#### Para Tenant User
```http
Authorization: Bearer {jwt_token}
X-Tenant-Subdomain: {subdomain}
```

### Validação de Token

- **Expiração:** 1 hora (3600 segundos)
- **Refresh:** Disponível via endpoint `/refresh`
- **Revogação:** Sessões podem ser revogadas individualmente ou em massa

### Rate Limiting

- **Padrão:** 60 requisições por minuto por IP
- **Login:** 5 tentativas por minuto por IP
- **Upload:** 10 arquivos por minuto por usuário

---

## 📝 Códigos de Resposta

### Sucesso
- **200:** OK - Requisição processada com sucesso
- **201:** Created - Recurso criado com sucesso
- **204:** No Content - Requisição processada sem retorno

### Erro do Cliente
- **400:** Bad Request - Dados inválidos
- **401:** Unauthorized - Token inválido ou expirado
- **403:** Forbidden - Acesso negado
- **404:** Not Found - Recurso não encontrado
- **422:** Unprocessable Entity - Validação falhou
- **429:** Too Many Requests - Rate limit excedido

### Erro do Servidor
- **500:** Internal Server Error - Erro interno do servidor
- **502:** Bad Gateway - Erro de gateway
- **503:** Service Unavailable - Serviço indisponível

---

## 📊 Estrutura de Respostas

### Resposta de Sucesso Padrão
```json
{
    "success": true,
    "message": "Operação realizada com sucesso",
    "data": {
        // Dados específicos da operação
    }
}
```

### Resposta de Erro Padrão
```json
{
    "success": false,
    "message": "Descrição do erro",
    "errors": {
        "field": ["Mensagem de erro específica"]
    }
}
```

### Resposta com Paginação
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "...",
        "from": 1,
        "last_page": 5,
        "last_page_url": "...",
        "next_page_url": "...",
        "path": "...",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

---

## 🚀 Exemplos de Uso

### Frontend React/Vue

#### Configuração do Axios
```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    timeout: 10000
});

// Interceptor para adicionar token
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Interceptor para refresh automático
api.interceptors.response.use(
    response => response,
    async error => {
        if (error.response.status === 401) {
            // Tentar refresh do token
            const refreshToken = localStorage.getItem('refreshToken');
            if (refreshToken) {
                try {
                    const response = await api.post('/auth/refresh', { refresh_token: refreshToken });
                    localStorage.setItem('token', response.data.token);
                    return api.request(error.config);
                } catch (refreshError) {
                    // Redirecionar para login
                    window.location.href = '/login';
                }
            }
        }
        return Promise.reject(error);
    }
);
```

#### Exemplo de Upload de Avatar
```javascript
const updateAvatar = async (file) => {
    const formData = new FormData();
    formData.append('avatar', file);

    try {
        const response = await api.put('/profile/avatar', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            // Atualizar estado do usuário
            setUser(prev => ({
                ...prev,
                avatar: response.data.data.avatar_url
            }));
        }
    } catch (error) {
        console.error('Erro ao atualizar avatar:', error);
    }
};
```

### Mobile (React Native)

#### Configuração do Axios para Mobile
```javascript
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

const api = axios.create({
    baseURL: 'https://api.meusite.com/api',
    timeout: 15000
});

// Interceptor para token
api.interceptors.request.use(async config => {
    const token = await AsyncStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
```

---

## 🔧 Configuração do Ambiente

### Variáveis de Ambiente
```env
APP_NAME="SaaS Automotivo"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.meusite.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=saas_automotivo
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=seu_jwt_secret_aqui
JWT_TTL=3600
JWT_REFRESH_TTL=7200

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu_email@gmail.com
MAIL_PASSWORD=sua_senha_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Configuração do CORS
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

---

## 📚 Recursos Adicionais

### 📖 Documentação Swagger
- **URL:** `/api/documentation`
- **Autenticação:** Requerida para endpoints protegidos
- **Testes:** Interface interativa para testar endpoints

### 🧪 Testes
```bash
# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=ProfileTest

# Executar testes com coverage
php artisan test --coverage
```

### 📊 Monitoramento
- **Logs:** `storage/logs/laravel.log`
- **Cache:** Redis/Memcached para performance
- **Queue:** Processamento assíncrono de tarefas
- **Horizon:** Dashboard para monitorar filas

---

## 🆘 Suporte

### 📧 Contato
- **Email:** suporte@meusite.com
- **Documentação:** https://docs.meusite.com
- **GitHub:** https://github.com/meusite/saas-automotivo

### 🐛 Reportar Bugs
1. Verificar se o bug já foi reportado
2. Criar issue com descrição detalhada
3. Incluir logs e steps para reproduzir
4. Adicionar screenshots se aplicável

### 💡 Sugestões
- Criar issue com label "enhancement"
- Descrever funcionalidade desejada
- Explicar benefícios para os usuários

---

## 📅 Histórico de Versões

### v1.0.0 (2024-08-24)
- ✅ Sistema de autenticação JWT
- ✅ Gerenciamento de tenants
- ✅ Gerenciamento de usuários
- ✅ Gerenciamento de veículos
- ✅ Sistema de configurações
- ✅ Módulo de perfil completo
- ✅ Analytics e relatórios
- ✅ Portal público
- ✅ API documentada

---

*Última atualização: 24 de Agosto de 2024*
*Versão da API: 1.0.0*
