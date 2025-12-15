# 🤖 **SISTEMA DE ROBOTS.TXT - SUPER ADMIN**

## 📋 **Visão Geral**

Sistema completo para gerenciamento de arquivos `robots.txt` por tenant, com acesso exclusivo para Super Admin. Os arquivos são salvos em `storage/app/robots/{tenant-subdomain}/robots.txt` para persistência em produção.

## 🗂️ **Estrutura de Armazenamento**

```
storage/app/robots/
├── .gitkeep
├── tenant1/
│   └── robots.txt
├── tenant2/
│   └── robots.txt
└── tenant3/
    └── robots.txt
```

## 🚀 **Endpoints Disponíveis**

### **Super Admin (Autenticação Obrigatória)**

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/api/super-admin/robots/configs` | GET | Listar configurações |
| `/api/super-admin/robots/configs` | POST | Criar configuração |
| `/api/super-admin/robots/configs/{id}` | GET | Obter configuração específica |
| `/api/super-admin/robots/configs/{id}` | PUT | Atualizar configuração |
| `/api/super-admin/robots/configs/{id}` | DELETE | Deletar configuração |
| `/api/super-admin/robots/generate` | POST | Gerar robots.txt |
| `/api/super-admin/robots/preview` | GET | Preview do robots.txt |

### **Público (Sem Autenticação)**

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/api/robots/serve` | GET | Servir arquivo robots.txt |
| `/api/tenant/robots-txt/` | GET | Ler configuração do tenant |

### **Sitemaps (Mesma Estrutura)**

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/api/super-admin/sitemap/configs` | POST/PUT/DELETE | CRUD (Super Admin) |
| `/api/super-admin/sitemap/generate` | POST | Gerar sitemap (Super Admin) |
| `/api/seo/sitemap-file` | GET | Servir arquivo sitemap (Público) |
| `/api/seo/sitemap-index` | GET | Sitemap index (Público) |
| `/api/tenant/sitemap/configs` | GET | Ler configurações (Público) |

## 🌐 **Acesso Público**

O arquivo robots.txt pode ser acessado de duas formas:

### **1. Via API (Recomendado)**
```bash
GET /api/robots/serve?tenant=omegaveiculos
```

### **2. Via Link Simbólico**
```bash
GET /robots/omegaveiculos/robots.txt
```

**Estrutura de acesso:**
- **Storage**: `storage/app/robots/{tenant}/robots.txt`
- **Link simbólico**: `public/robots/{tenant}/robots.txt`
- **URL pública**: `https://domain.com/robots/{tenant}/robots.txt`

## 🔧 **Estrutura da Tabela**

### **tenant_robots_configs**
```sql
- id (bigint, PK)
- tenant_id (bigint, FK)
- locale (varchar(10))
- is_active (boolean)
- user_agent_rules (text, JSON)
- disallow_rules (text, JSON)
- allow_rules (text, JSON)
- crawl_delay (text, JSON)
- sitemap_urls (text, JSON)
- custom_rules (text)
- host_directive (text)
- include_sitemap_index (boolean)
- include_sitemap_files (boolean)
- notes (text)
- last_generated_at (timestamp)
- last_generated_by (varchar)
- created_at, updated_at
```

## 📝 **Exemplo de Conteúdo Gerado**

```txt
Host: www.example.com

User-agent: *
Allow: /
Disallow: /admin/
Disallow: /private/
Disallow: /api/
Crawl-delay: 1

# Custom rules
# Regras personalizadas aqui

Sitemap: https://tenant.localhost/sitemap-index.xml
Sitemap: https://tenant.localhost/sitemap-vehicle_detail.xml
Sitemap: https://tenant.localhost/sitemap-collection.xml
Sitemap: https://tenant.localhost/sitemap-blog_post.xml
Sitemap: https://tenant.localhost/sitemap-faq.xml
Sitemap: https://tenant.localhost/sitemap-static.xml
```

## 🔐 **Controle de Acesso**

| Funcionalidade | Acesso | Autenticação | Descrição |
|----------------|--------|---------------|-----------|
| **CRUD Configurações** | Super Admin | ✅ Obrigatória | Criar, ler, atualizar, deletar |
| **Gerar robots.txt** | Super Admin | ✅ Obrigatória | Gerar e salvar arquivo |
| **Preview** | Super Admin | ✅ Obrigatória | Preview sem salvar |
| **Servir arquivo** | **Público** | ❌ Não requerida | Acesso direto ao robots.txt |
| **Ler configuração** | **Público** | ❌ Não requerida | Leitura via tenant.auto |

### 🚨 **IMPORTANTE: Segurança**

- **✅ Rotas de Escrita**: Apenas Super Admin pode criar, atualizar e deletar
- **✅ Rotas de Leitura**: Públicas para Google e motores de busca
- **✅ Middleware**: `token.auth.super_admin` para operações de escrita
- **✅ Middleware**: `tenant.auto` para leitura pública

## 🎯 **Funcionalidades**

### ✅ **Configuração Flexível**
- Múltiplos user-agents
- Regras Allow/Disallow personalizadas
- Crawl delay por user-agent
- Regras customizadas em texto livre

### ✅ **Integração com Sitemaps**
- Inclusão automática de sitemap index
- Inclusão automática de sitemaps por tipo
- URLs de sitemap personalizadas

### ✅ **Gestão por Locale**
- Suporte a múltiplos idiomas
- Configuração específica por locale
- Fallback para configuração padrão

### ✅ **Auditoria**
- Registro de última geração
- Identificação do usuário gerador
- Logs detalhados de operações

## 🔧 **Configuração Padrão**

```php
[
    'tenant_id' => $tenantId,
    'locale' => 'pt-BR',
    'is_active' => true,
    'user_agent_rules' => [
        '*' => [
            'allow' => ['/'],
            'disallow' => ['/admin/', '/private/', '/temp/', '/api/'],
            'crawl_delay' => 1
        ]
    ],
    'sitemap_urls' => [],
    'include_sitemap_index' => true,
    'include_sitemap_files' => true,
    'custom_rules' => null,
    'host_directive' => null,
    'notes' => 'Configuração padrão gerada automaticamente'
]
```

## 📊 **Fluxo de Trabalho**

1. **Super Admin** autentica com token
2. **Super Admin** cria/atualiza configuração via API
3. **Super Admin** gera robots.txt via `/generate`
4. **Sistema** salva arquivo em `storage/app/robots/{tenant}/robots.txt`
5. **Arquivo** fica disponível para acesso público via servidor web

## 🚀 **Uso Prático**

### **Super Admin (Autenticação Obrigatória)**

```bash
# 1. Criar configuração
curl -H "Authorization: Bearer TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"tenant_id": 1, "locale": "pt-BR"}' \
     "https://api.domain.com/api/super-admin/robots/configs"

# 2. Gerar robots.txt
curl -H "Authorization: Bearer TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"tenant_id": 1, "locale": "pt-BR"}' \
     "https://api.domain.com/api/super-admin/robots/generate"

# 3. Preview antes de salvar
curl -H "Authorization: Bearer TOKEN" \
     "https://api.domain.com/api/super-admin/robots/preview?tenant_id=1&locale=pt-BR"
```

### **Público (Sem Autenticação)**

```bash
# 4. Acessar robots.txt via API
curl "https://api.domain.com/api/robots/serve?tenant=omegaveiculos"

# 5. Acessar robots.txt via link simbólico
curl "https://domain.com/robots/omegaveiculos/robots.txt"
```

## ✅ **Vantagens**

- **🔒 Segurança**: Acesso exclusivo Super Admin para configuração
- **🌐 Público**: Arquivo robots.txt acessível publicamente
- **💾 Persistência**: Arquivos salvos em storage
- **🌍 Multi-tenant**: Configuração por tenant
- **🌍 Multi-locale**: Suporte a múltiplos idiomas
- **📊 Auditoria**: Logs completos de operações
- **🔧 Flexibilidade**: Configuração altamente personalizável
- **📈 Escalabilidade**: Estrutura preparada para crescimento
- **🔗 Acesso Duplo**: Via API e link simbólico

**Sistema completo de robots.txt implementado com sucesso!** 🤖✨
