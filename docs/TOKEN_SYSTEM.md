# Sistema de Tokens Unificado - SaaS Automotivo

## 🎯 Visão Geral

O sistema implementa um **sistema de tokens unificado** que aceita automaticamente tanto **JWT tokens** quanto **Laravel Sanctum tokens**, proporcionando máxima flexibilidade e compatibilidade para o frontend.

## 🔄 Tipos de Token Suportados

### 1. **JWT Tokens**
- **Formato**: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`
- **Características**:
  - 3 partes separadas por ponto (header.payload.signature)
  - Expiração configurável
  - Payload customizável
- **Uso**: Frontend existente, sistemas legados

### 2. **Sanctum Tokens**
- **Formato**: `3|TyEf1awvl7Lj7barUD2ac3uB35vowlq8dPz59yCi41652f71`
- **Características**:
  - Formato: `id|hash`
  - Sem expiração automática
  - Gerenciamento de sessões
- **Uso**: Novos sistemas, Laravel nativo

## 🛠️ Como Funciona

### **Detecção Automática**
```php
// O sistema detecta automaticamente o tipo de token
$tokenType = TokenHelper::detectTokenType($token);
// Retorna: 'jwt', 'sanctum' ou 'unknown'
```

### **Autenticação Unificada**
```php
// Funciona com ambos os tipos de token
$user = TokenHelper::getAuthenticatedUser($request);
```

### **Middleware Simplificado**
```php
// Antes: auth:sanctum, jwt.auth, auth:api
// Agora: token.auth.super_admin, token.auth.tenant
Route::middleware(['token.auth.super_admin'])->group(function () {
    // Endpoints protegidos
});
```

## 📍 Endpoints Atualizados

### **Super Admin (tabela Users)**
```php
Route::middleware(['token.auth.super_admin'])->group(function () {
    Route::get('me', [SuperAdminAuthController::class, 'me']);
    Route::prefix('tenants')->group(function () { /* ... */ });
    Route::prefix('other-config')->group(function () { /* ... */ });
    Route::prefix('profile')->group(function () { /* ... */ });
});
```

### **Admin Client (tabela TenantUsers)**
```php
Route::middleware(['token.auth.tenant'])->group(function () {
    Route::get('me', [TenantAuthController::class, 'me']);
    Route::get('profile', [TenantUserProfileController::class, 'show']);
    Route::get('dashboard', [DashboardController::class, 'index']);
});
```

### **Analytics e Configurações**
```php
Route::middleware(['token.auth.tenant', 'tenant.identification'])->group(function () {
    Route::prefix('analytics')->group(function () { /* ... */ });
    Route::prefix('site-config')->group(function () { /* ... */ });
    Route::prefix('profile')->group(function () { /* ... */ });
});
```

## 🔐 Segurança e Roles

### **Verificação de Roles**
```php
// Verifica automaticamente se o usuário tem a role necessária
Route::middleware(['token.auth.super_admin'])->group(function () {
    // Apenas Super Admins podem acessar
});

Route::middleware(['token.auth.tenant'])->group(function () {
    // Apenas Tenant Admins podem acessar
});
```

### **Validação de Tenant**
```php
// Middleware adicional para identificação de tenant
Route::middleware(['token.auth.tenant', 'tenant.identification'])->group(function () {
    // Endpoints que precisam identificar o tenant automaticamente
});
```

## 🧪 Exemplos de Uso

### **Login JWT**
```bash
curl -X POST "http://127.0.0.1:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@demo.com",
    "password": "password123",
    "tenant_subdomain": "demo"
  }'
```

### **Login Sanctum**
```bash
curl -X POST "http://127.0.0.1:8000/api/tenant/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@demo.com",
    "password": "password123"
  }'
```

### **Acesso com Qualquer Token**
```bash
# Funciona com JWT
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  http://127.0.0.1:8000/api/dashboard

# Funciona com Sanctum
curl -H "Authorization: Bearer 3|TyEf1awvl7Lj7barUD2ac3uB35vowlq8dPz59yCi41652f71" \
  http://127.0.0.1:8000/api/dashboard
```

## 🏗️ Arquitetura

### **TokenHelper**
- **Localização**: `app/Helpers/TokenHelper.php`
- **Responsabilidade**: Detecção e autenticação de tokens
- **Métodos principais**:
  - `getAuthenticatedUser()`
  - `detectTokenType()`
  - `authenticateUser()`
  - `hasRole()`

### **TokenAuthMiddleware**
- **Localização**: `app/Http/Middleware/TokenAuthMiddleware.php`
- **Responsabilidade**: Middleware unificado para autenticação
- **Registro**: `bootstrap/app.php`

### **Compatibilidade**
- **JWT**: Usa `Tymon\JWTAuth\Facades\JWTAuth`
- **Sanctum**: Usa `Laravel\Sanctum\PersonalAccessToken`
- **Fallback**: Tenta ambos os métodos automaticamente

## 📊 Benefícios

### **Para Desenvolvedores**
1. **Flexibilidade**: Aceita qualquer tipo de token
2. **Simplicidade**: Um middleware para todos os casos
3. **Manutenibilidade**: Lógica centralizada
4. **Debugging**: Fácil identificação de problemas

### **Para Frontend**
1. **Compatibilidade**: Funciona com tokens existentes
2. **Migração Gradual**: Pode migrar quando conveniente
3. **Transparência**: Não precisa saber o tipo de token
4. **Estabilidade**: Sistema robusto e confiável

### **Para Sistema**
1. **Segurança**: Verificação de roles integrada
2. **Performance**: Detecção rápida de tipo de token
3. **Escalabilidade**: Fácil adição de novos tipos
4. **Monitoramento**: Logs centralizados

## 🚀 Próximos Passos

### **Implementação**
- ✅ Sistema de tokens unificado implementado
- ✅ Todos os endpoints atualizados
- ✅ Documentação Swagger atualizada
- ✅ Middleware registrado e funcionando

### **Testes**
- ✅ Sanctum tokens funcionando
- 🔄 JWT tokens em validação
- 🔄 Tokens mistos em teste
- 🔄 Performance e segurança

### **Documentação**
- ✅ Swagger atualizado
- ✅ Guia de tokens criado
- 🔄 Exemplos de uso
- 🔄 Troubleshooting

## 🔧 Troubleshooting

### **Token Inválido**
```bash
# Verificar se o token está sendo enviado corretamente
curl -H "Authorization: Bearer SEU_TOKEN" \
  http://127.0.0.1:8000/api/dashboard

# Verificar logs do Laravel
tail -f storage/logs/laravel.log
```

### **Role Insuficiente**
```bash
# Verificar se o usuário tem a role necessária
# Verificar se está usando o middleware correto
Route::middleware(['token.auth.super_admin']) // Para Super Admin
Route::middleware(['token.auth.tenant'])      // Para Tenant Admin
```

### **Tenant Não Identificado**
```bash
# Verificar se o middleware tenant.identification está sendo usado
# Verificar se o tenant está ativo no banco
```

## 📞 Suporte

Para dúvidas ou problemas com o sistema de tokens:

- **Email**: admin@saas-automotivo.com
- **Documentação**: `/docs/TOKEN_SYSTEM.md`
- **Swagger**: `/api/documentation`
- **Logs**: `storage/logs/laravel.log`

---

**Sistema de Tokens Unificado** - SaaS Automotivo v1.0.0
*Compatibilidade total com JWT e Sanctum* 🚀
