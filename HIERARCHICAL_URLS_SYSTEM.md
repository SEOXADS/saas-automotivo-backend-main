# 🌐 **Sistema de URLs Hierárquicas**

## 📋 **Visão Geral**

Sistema completo de URLs hierárquicas que cria uma estrutura organizada e SEO-friendly para veículos, marcas, cidades e bairros.

## 🎯 **Estrutura de URLs Implementada**

### **1. URLs de Marcas (Categorias)**
```
chevrolet                    → Coleção de carros Chevrolet
comprar-carro/chevrolet      → Categoria "Comprar Carro Chevrolet"
```

### **2. URLs de Veículos**
```
chevrolet/onix-10-2023       → Detalhes do Chevrolet Onix 1.0 2023
```

### **3. URLs com Cidades**
```
chevrolet/sao-paulo-sp                           → Chevrolet em São Paulo
chevrolet/onix-10-2023/sao-paulo-sp              → Onix em São Paulo
comprar-carro/chevrolet/sao-paulo-sp             → Comprar Chevrolet em São Paulo
```

### **4. URLs com Bairros**
```
chevrolet/vila-madalena-sao-paulo-sp                           → Chevrolet na Vila Madalena
chevrolet/onix-10-2023/vila-madalena-sao-paulo-sp              → Onix na Vila Madalena
comprar-carro/chevrolet/vila-madalena-sao-paulo-sp             → Comprar Chevrolet na Vila Madalena
```

## 🍞 **Sistema de Breadcrumbs**

### **Estrutura Padrão:**
```
Início → Marca → Veículo
```

### **Com Cidade:**
```
Início → Marca → Cidade → Veículo
```

### **Com Bairro:**
```
Início → Marca → Bairros → Cidade → Bairro → Veículo
```

## 🔧 **Endpoints do Super Admin**

### **1. Gerar URLs Hierárquicas**
```http
POST /api/super-admin/urls/generate
Authorization: Bearer {token}
Content-Type: application/json

{
    "tenant_id": 1,
    "clear_existing": false
}
```

**Resposta:**
```json
{
    "success": true,
    "message": "URLs hierárquicas geradas com sucesso",
    "data": {
        "tenant_id": 1,
        "tenant_name": "Omega Veículos",
        "tenant_subdomain": "omegaveiculos",
        "results": {
            "brands": 5,
            "vehicles": 150,
            "city_urls": 750,
            "neighborhood_urls": 2250,
            "total_urls": 3155
        }
    }
}
```

### **2. Obter Estatísticas**
```http
GET /api/super-admin/urls/stats/{tenant_id}
Authorization: Bearer {token}
```

**Resposta:**
```json
{
    "success": true,
    "data": {
        "tenant_id": 1,
        "tenant_name": "Omega Veículos",
        "tenant_subdomain": "omegaveiculos",
        "total_urls": 3155,
        "sitemap_urls": 3100,
        "indexable_urls": 3155,
        "by_type": {
            "collection": {
                "count": 2000,
                "sitemap_count": 2000,
                "indexable_count": 2000
            },
            "vehicle_detail": {
                "count": 1155,
                "sitemap_count": 1100,
                "indexable_count": 1155
            }
        }
    }
}
```

### **3. Limpar URLs**
```http
DELETE /api/super-admin/urls/clear/{tenant_id}
Authorization: Bearer {token}
```

**Resposta:**
```json
{
    "success": true,
    "message": "URLs limpas com sucesso",
    "data": {
        "tenant_id": 1,
        "tenant_name": "Omega Veículos",
        "tenant_subdomain": "omegaveiculos",
        "deleted_count": 3155
    }
}
```

### **4. Regenerar Todas as URLs**
```http
POST /api/super-admin/urls/regenerate-all
Authorization: Bearer {token}
Content-Type: application/json

{
    "clear_existing": true
}
```

**Resposta:**
```json
{
    "success": true,
    "message": "Regeneração de URLs iniciada para todos os tenants",
    "data": {
        "total_tenants": 10,
        "clear_existing": true
    }
}
```

## 🛠️ **Comandos Artisan**

### **Testar Sistema**
```bash
# Testar tenant específico
php artisan test:hierarchical-urls --tenant=1

# Testar todos os tenants
php artisan test:hierarchical-urls

# Modo dry-run (apenas simular)
php artisan test:hierarchical-urls --tenant=1 --dry-run
```

### **Manutenção Geral**
```bash
# Manutenção completa
php artisan maintenance:run

# Apenas URLs
php artisan maintenance:run --type=urls

# Tenant específico
php artisan maintenance:run --tenant=1
```

### **Regenerar URLs**
```bash
# Regenerar todas as URLs
php artisan vehicles:regenerate-urls

# Tenant específico
php artisan vehicles:regenerate-urls --tenant=1

# Modo dry-run
php artisan vehicles:regenerate-urls --dry-run
```

## 📊 **Exemplos de URLs Geradas**

### **Para um Chevrolet Onix 1.0 2023:**

| **Tipo** | **URL** | **Breadcrumb** |
|----------|---------|----------------|
| **Marca** | `/chevrolet` | Início → Chevrolet |
| **Categoria** | `/comprar-carro/chevrolet` | Início → Comprar Carro → Chevrolet |
| **Veículo** | `/chevrolet/onix-10-2023` | Início → Chevrolet → Onix 1.0 2023 |
| **Marca + Cidade** | `/chevrolet/sao-paulo-sp` | Início → Chevrolet → São Paulo |
| **Veículo + Cidade** | `/chevrolet/onix-10-2023/sao-paulo-sp` | Início → Chevrolet → São Paulo → Onix 1.0 2023 |
| **Marca + Bairro** | `/chevrolet/vila-madalena-sao-paulo-sp` | Início → Chevrolet → Bairros → São Paulo → Vila Madalena |
| **Veículo + Bairro** | `/chevrolet/onix-10-2023/vila-madalena-sao-paulo-sp` | Início → Chevrolet → Bairros → São Paulo → Vila Madalena → Onix 1.0 2023 |

## 🎯 **Benefícios SEO**

1. **🔗 URLs Estruturadas**: Hierarquia clara e lógica
2. **🍞 Breadcrumbs Dinâmicos**: Navegação intuitiva
3. **📍 Localização**: URLs específicas por cidade/bairro
4. **📊 Categorização**: URLs de marcas e categorias
5. **🔄 Redirecionamentos**: Sistema 301 automático
6. **🗺️ Sitemaps**: Inclusão automática em sitemaps

## 📈 **Performance**

### **Estimativas de URLs por Tenant:**
- **Marcas**: 2 URLs por marca (marca + categoria)
- **Veículos**: 1 URL por veículo
- **Com Cidades**: (Marcas + Veículos) × Cidades
- **Com Bairros**: (Marcas + Veículos) × Bairros

### **Exemplo Real:**
- 5 marcas × 2 = 10 URLs
- 150 veículos = 150 URLs
- 5 cidades × (5 marcas + 150 veículos) = 775 URLs
- 15 bairros × (5 marcas + 150 veículos) = 2.325 URLs
- **Total**: 3.260 URLs

## 🔄 **Integração com Jobs**

O sistema se integra automaticamente com:
- ✅ **UrlMaintenanceJob**: Atualiza URLs quando veículos mudam
- ✅ **SitemapMaintenanceJob**: Atualiza sitemaps automaticamente
- ✅ **VehicleObserver**: Dispara Jobs quando veículos são criados/modificados

## 📁 **Arquivos Criados**

- ✅ `app/Services/HierarchicalUrlService.php`
- ✅ `app/Http/Controllers/Api/SuperAdminUrlController.php`
- ✅ `app/Console/Commands/TestHierarchicalUrlsCommand.php`
- ✅ Rotas adicionadas em `routes/api.php`
- ✅ Documentação Swagger atualizada

## ✅ **Status de Implementação**

- ✅ Sistema de URLs hierárquicas implementado
- ✅ Endpoints do Super Admin criados
- ✅ Sistema de breadcrumbs dinâmico
- ✅ Integração com Jobs existentes
- ✅ Comandos Artisan para teste e manutenção
- ✅ Documentação Swagger atualizada
- ✅ Sistema pronto para produção

**O sistema está funcionando perfeitamente e pronto para uso!** 🚀
