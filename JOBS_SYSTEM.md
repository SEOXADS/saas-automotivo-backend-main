# 🔧 **Sistema de Jobs para Manutenção de URLs e Sitemaps**

## 📋 **Visão Geral**

Sistema automatizado para gerenciar URLs de veículos e sitemaps através de Jobs em background, garantindo consistência e performance.

## 🚀 **Componentes Implementados**

### 1. **UrlMaintenanceJob**
**Arquivo:** `app/Jobs/UrlMaintenanceJob.php`

**Responsabilidades:**
- ✅ Gerar URLs únicas para novos veículos
- ✅ Atualizar URLs quando veículos são modificados
- ✅ Criar redirecionamentos 301 automáticos
- ✅ Limpar redirecionamentos quando veículos são deletados

**Ações Suportadas:**
- `create`: Gera URL para novo veículo
- `update`: Atualiza URL e cria redirecionamento 301 se necessário
- `delete`: Desativa redirecionamentos relacionados

### 2. **SitemapMaintenanceJob**
**Arquivo:** `app/Jobs/SitemapMaintenanceJob.php`

**Responsabilidades:**
- ✅ Gerar sitemaps para tenants específicos
- ✅ Atualizar sitemaps quando veículos mudam
- ✅ Regenerar todos os sitemaps do sistema
- ✅ Criar configurações padrão de sitemap

**Ações Suportadas:**
- `generate`: Gera sitemap para tenant específico
- `update`: Atualiza sitemap existente
- `regenerate_all`: Regenera todos os sitemaps

### 3. **VehicleObserver**
**Arquivo:** `app/Observers/VehicleObserver.php`

**Responsabilidades:**
- ✅ Disparar Jobs automaticamente quando veículos são criados/modificados/deletados
- ✅ Detectar mudanças no título que afetam a URL
- ✅ Coordenar Jobs de URL e sitemap

**Eventos Monitorados:**
- `created`: Dispara Jobs de criação
- `updated`: Dispara Jobs de atualização (apenas se título mudou)
- `deleted`: Dispara Jobs de limpeza
- `restored`: Dispara Jobs de restauração

## 🎯 **Fluxo de Funcionamento**

### **Criação de Veículo:**
```
1. Vehicle::create() → VehicleObserver::created()
2. UrlMaintenanceJob::dispatch(vehicle_id, 'create')
3. SitemapMaintenanceJob::dispatch(tenant_id, 'update')
4. URL gerada e sitemap atualizado automaticamente
```

### **Atualização de Veículo:**
```
1. Vehicle::update() → VehicleObserver::updated()
2. Detecta mudança no título
3. UrlMaintenanceJob::dispatch(vehicle_id, 'update', old_url)
4. SitemapMaintenanceJob::dispatch(tenant_id, 'update')
5. Nova URL gerada + redirecionamento 301 criado
```

### **Exclusão de Veículo:**
```
1. Vehicle::delete() → VehicleObserver::deleted()
2. UrlMaintenanceJob::dispatch(vehicle_id, 'delete')
3. SitemapMaintenanceJob::dispatch(tenant_id, 'update')
4. Redirecionamentos desativados + sitemap atualizado
```

## 🛠️ **Comandos Artisan**

### **1. Manutenção Geral**
```bash
# Manutenção completa
php artisan maintenance:run

# Apenas URLs
php artisan maintenance:run --type=urls

# Apenas sitemaps
php artisan maintenance:run --type=sitemaps

# Tenant específico
php artisan maintenance:run --tenant=1

# Forçar execução
php artisan maintenance:run --force
```

### **2. Regeneração de URLs**
```bash
# Regenerar todas as URLs
php artisan vehicles:regenerate-urls

# Tenant específico
php artisan vehicles:regenerate-urls --tenant=1

# Modo dry-run (apenas mostrar)
php artisan vehicles:regenerate-urls --dry-run

# Tamanho do lote
php artisan vehicles:regenerate-urls --batch-size=50
```

## 📊 **Sistema de Redirecionamento 301**

### **Quando é Criado:**
- ✅ Título do veículo é alterado
- ✅ URL gerada muda devido às novas regras
- ✅ Veículo é restaurado com título diferente

### **Como Funciona:**
```php
// Exemplo de redirecionamento criado
TenantUrlRedirect::create([
    'tenant_id' => 1,
    'from_url' => 'honda-civic-10-2023',           // URL antiga
    'to_url' => 'honda-civic-automatico-10-2023',  // URL nova
    'redirect_type' => '301',
    'redirect_reason' => 'vehicle_url_changed',
    'is_active' => true
]);
```

### **Middleware de Redirecionamento:**
- ✅ `UrlRedirectMiddleware` intercepta requisições
- ✅ Verifica tabela `tenant_url_redirects`
- ✅ Aplica redirecionamento 301 automaticamente

## 🔄 **Configuração de Queue**

### **Para Produção:**
```bash
# Instalar supervisor ou usar queue worker
php artisan queue:work --queue=default --tries=3 --timeout=300

# Ou configurar cron para processar jobs
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### **Para Desenvolvimento:**
```bash
# Processar jobs sincronamente
php artisan queue:work --once

# Ou usar sync driver no .env
QUEUE_CONNECTION=sync
```

## 📈 **Monitoramento e Logs**

### **Logs Gerados:**
- ✅ Criação/atualização de URLs
- ✅ Criação de redirecionamentos 301
- ✅ Geração de sitemaps
- ✅ Erros e exceções

### **Exemplo de Log:**
```log
[2025-01-16 10:30:15] local.INFO: URL gerada para novo veículo {"vehicle_id":123,"title":"Honda Civic 1.0 2023","url":"honda-civic-10-2023","tenant_id":1}
[2025-01-16 10:30:16] local.INFO: Redirecionamento 301 criado {"vehicle_id":123,"from_url":"honda-civic-10-2023","to_url":"honda-civic-automatico-10-2023","tenant_id":1}
[2025-01-16 10:30:17] local.INFO: Sitemap gerado com sucesso {"tenant_id":1,"tenant_subdomain":"omegaveiculos"}
```

## 🎯 **Benefícios**

1. **🔄 Automatização**: URLs e sitemaps atualizados automaticamente
2. **⚡ Performance**: Processamento em background
3. **🔗 SEO**: Redirecionamentos 301 preservam ranking
4. **📊 Consistência**: Regras aplicadas uniformemente
5. **🛠️ Manutenibilidade**: Sistema centralizado e monitorado
6. **🎛️ Flexibilidade**: Comandos para manutenção manual

## ✅ **Status de Implementação**

- ✅ UrlMaintenanceJob implementado
- ✅ SitemapMaintenanceJob implementado
- ✅ VehicleObserver implementado
- ✅ Comandos Artisan criados
- ✅ Sistema de redirecionamento 301 funcional
- ✅ Logs e monitoramento configurados
- ✅ Documentação completa

**Sistema pronto para uso em produção!** 🚀
