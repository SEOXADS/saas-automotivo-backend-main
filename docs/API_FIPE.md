# 🚗 **API FIPE - Documentação Completa**

## 📋 **Visão Geral**

A API FIPE foi integrada ao sistema SaaS automotivo para fornecer dados oficiais da tabela FIPE (Fundação Instituto de Pesquisas Econômicas) para todos os tipos de usuários: **Super Admin**, **Admin Client** e **Portal Público**.

## 🔐 **Configuração**

### **Variáveis de Ambiente (.env)**
```env
# API FIPE Configuration
FIPE_API_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
FIPE_BASE_URL=https://fipe.parallelum.com.br/api/v2
FIPE_RATE_LIMIT_PER_DAY=500
FIPE_CACHE_TTL=86400
```

### **Token de Acesso**
- **Fonte**: [Fipe Online](https://fipe.online/docs/comece-aqui)
- **Limite**: 500 requisições por dia (gratuito)
- **Atualização**: Dados mensais da tabela FIPE oficial

## 🏗️ **Arquitetura**

### **Componentes Implementados**
1. **FipeService** - Serviço principal de integração
2. **FipeController** - Controller para usuários autenticados
3. **PublicFipeController** - Controller para acesso público
4. **FipeRateLimitMiddleware** - Controle de rate limiting
5. **Cache Inteligente** - Otimização de performance

### **Estratégia de Cache**
- **TTL**: 24 horas (dados mensais da FIPE)
- **Chaves**: Estruturadas por endpoint e parâmetros
- **Limpeza**: Automática e manual (Super Admin)

## 🔒 **Endpoints por Tipo de Usuário**

### **👑 Super Admin (Gestão do SaaS)**
**Base URL**: `/api/fipe`
**Middleware**: `token.auth.super_admin`

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/references` | GET | Referências de meses da FIPE |
| `/brands/{type}` | GET | Marcas por tipo de veículo |
| `/brands/{type}/{brandId}/models` | GET | Modelos por marca |
| `/brands/{type}/{brandId}/models/{modelId}/years` | GET | Anos por modelo |
| `/vehicle/{type}/{brandId}/{modelId}/{yearId}` | GET | Informações completas do veículo |
| `/search` | GET | Busca avançada de veículos |
| `/search/code/{codeFipe}` | GET | Busca por código FIPE |
| `/status` | GET | Status da API FIPE |
| `/usage-stats` | GET | Estatísticas de uso (exclusivo) |
| `/cache/clear` | POST | Limpar cache (exclusivo) |

### **👨‍💼 Admin Client (Tenant)**
**Base URL**: `/api/fipe`
**Middleware**: `token.auth.tenant`

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/references` | GET | Referências de meses da FIPE |
| `/brands/{type}` | GET | Marcas por tipo de veículo |
| `/brands/{type}/{brandId}/models` | GET | Modelos por marca |
| `/brands/{type}/{brandId}/models/{modelId}/years` | GET | Anos por modelo |
| `/vehicle/{type}/{brandId}/{modelId}/{yearId}` | GET | Informações completas do veículo |
| `/search` | GET | Busca avançada de veículos |
| `/search/code/{codeFipe}` | GET | Busca por código FIPE |
| `/status` | GET | Status da API FIPE |

### **🌐 Portal Público**
**Base URL**: `/api/public/fipe`
**Middleware**: `fipe.rate.limit`

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/references` | GET | Referências de meses da FIPE |
| `/brands/{type}` | GET | Marcas por tipo de veículo |
| `/brands/{type}/{brandId}/models` | GET | Modelos por marca |
| `/brands/{type}/{brandId}/models/{modelId}/years` | GET | Anos por modelo |
| `/search` | GET | Busca avançada de veículos |
| `/search/code/{codeFipe}` | GET | Busca por código FIPE |
| `/status` | GET | Status da API FIPE |
| `/calculate-price` | POST | Calculadora de preços |

## 📊 **Tipos de Veículo Suportados**

| Tipo | Descrição | Exemplo |
|------|-----------|---------|
| `cars` | Carros | Sedan, Hatch, SUV |
| `motorcycles` | Motocicletas | Street, Sport, Custom |
| `trucks` | Caminhões | Leve, Médio, Pesado |

## 🔍 **Exemplos de Uso**

### **1. Buscar Marcas de Carros**
```bash
curl "http://localhost:8000/api/public/fipe/brands/cars"
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "code": "59",
      "name": "VW - VolksWagen"
    },
    {
      "code": "23",
      "name": "GM - Chevrolet"
    }
  ],
  "vehicle_type": "cars",
  "note": "Dados da tabela FIPE oficial"
}
```

### **2. Buscar Modelos da Volkswagen**
```bash
curl "http://localhost:8000/api/public/fipe/brands/cars/59/models"
```

### **3. Buscar Anos do Polo**
```bash
curl "http://localhost:8000/api/public/fipe/brands/cars/59/models/8070/years"
```

### **4. Calcular Preço com Condição**
```bash
curl -X POST "http://localhost:8000/api/public/fipe/calculate-price" \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_type": "cars",
    "brand_id": 59,
    "model_id": 8070,
    "year_id": "2022-5",
    "condition": "good"
  }'
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "vehicle_info": {
      "price": "R$ 70.283,00",
      "brand": "VW - VolksWagen",
      "model": "Polo 1.0 Flex 12V 5p",
      "modelYear": 2022,
      "fuel": "Flex"
    },
    "price_calculation": {
      "base_price_fipe": "R$ 70.283,00",
      "condition": "good",
      "condition_factor": 1.05,
      "estimated_price": "R$ 73.797,15"
    }
  }
}
```

## ⚡ **Rate Limiting**

### **Limites Implementados**
- **Global**: 500 requisições por dia (API FIPE)
- **Por IP**: 100 requisições por hora
- **Por Usuário**: 50 requisições por hora (autenticados)

### **Respostas de Limite**
```json
{
  "error": "Limite diário de consultas à API FIPE atingido",
  "message": "O limite de 500 consultas por dia foi atingido. Tente novamente amanhã.",
  "rate_limit": 500,
  "reset_time": "2025-08-26T00:00:00.000000Z"
}
```

## 💰 **Calculadora de Preços**

### **Fatores de Condição**
| Condição | Fator | Descrição |
|----------|-------|-----------|
| `excellent` | 1.15 | 15% acima da FIPE |
| `good` | 1.05 | 5% acima da FIPE |
| `regular` | 0.95 | 5% abaixo da FIPE |
| `poor` | 0.80 | 20% abaixo da FIPE |

### **Fórmula**
```
Preço Estimado = Preço FIPE × Fator de Condição
```

## 🗄️ **Cache e Performance**

### **Estratégia de Cache**
- **TTL**: 24 horas (dados mensais)
- **Chaves**: Estruturadas por endpoint
- **Limpeza**: Automática e manual

### **Chaves de Cache**
```
fipe_references
fipe_brands_{type}_{reference}
fipe_models_{type}_{brandId}_{reference}
fipe_years_{type}_{brandId}_{modelId}_{reference}
fipe_vehicle_{type}_{brandId}_{modelId}_{yearId}_{reference}
```

## 📈 **Monitoramento (Super Admin)**

### **Estatísticas de Uso**
```bash
GET /api/fipe/usage-stats
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "date": "2025-08-25",
    "total_calls": 45,
    "calls_by_endpoint": {
      "brands": 15,
      "models": 20,
      "vehicle_info": 10
    },
    "remaining_calls": 455,
    "rate_limit": 500
  }
}
```

### **Limpeza de Cache**
```bash
POST /api/fipe/cache/clear
```

## 🚀 **Casos de Uso Práticos**

### **Para Super Admin**
- 📊 **Dashboard** com estatísticas de uso
- 💰 **Análise de custos** por tenant
- ⚠️ **Alertas** quando próximo do limite
- 🔄 **Sincronização** automática de dados

### **Para Admin Client**
- 🚗 **Cadastro de veículos** com preenchimento automático
- 💰 **Avaliação automática** baseada na FIPE
- 📊 **Relatórios** de valores por categoria
- 🔍 **Busca avançada** de veículos similares

### **Para Portal Público**
- 🧮 **Calculadora** de preços FIPE
- 📱 **Widgets** para sites de concessionárias
- 📊 **Comparadores** de preços
- 📈 **Tendências** de mercado

## 🔧 **Manutenção e Troubleshooting**

### **Verificar Status da API**
```bash
GET /api/public/fipe/status
```

### **Logs de Erro**
- **Localização**: `storage/logs/laravel.log`
- **Filtro**: `FIPE API Error` ou `FIPE API Exception`

### **Problemas Comuns**
1. **Token Expirado**: Renovar token na [Fipe Online](https://fipe.online)
2. **Rate Limit**: Aguardar reset diário ou limpar cache
3. **Cache Corrompido**: Usar endpoint de limpeza (Super Admin)

## 📚 **Recursos Adicionais**

### **Documentação Oficial**
- [Fipe Online API](https://fipe.online/docs/api/fipe)
- [Comece Aqui](https://fipe.online/docs/comece-aqui)

### **Suporte**
- **Email**: willian.saturnino@ictussistemas.com.br
- **Token ID**: f0e319a1-7887-4913-965b-bd029348432b

## 🎯 **Próximos Passos**

1. **📱 Frontend**: Implementar interfaces para cada tipo de usuário
2. **📊 Analytics**: Dashboard avançado de uso da API
3. **🔄 Sincronização**: Atualização automática de dados FIPE
4. **🔗 Integração**: Conectar com sistema de veículos existente
5. **📈 Relatórios**: Relatórios de valores por categoria

---

**✅ API FIPE implementada com sucesso!**
**🚀 Pronta para uso em produção!**
**📊 Dados oficiais da tabela FIPE!**
