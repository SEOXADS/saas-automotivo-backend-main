# 🔐 Credenciais para Testes da API - Swagger

## 📚 Acesso à Documentação Swagger

**URL da Documentação:** [http://127.0.0.1:8000/api/documentation](http://127.0.0.1:8000/api/documentation)

---

## 👑 Super Admin - Credenciais de Teste

### **Login do Super Admin**
**Endpoint:** `POST /api/super-admin/login`

### **Credenciais Disponíveis:**

| Email | Senha | Descrição |
|-------|-------|-----------|
| `admin@admin.com` | `123456` | ✅ **Principal** - Usuário master com todas as permissões |
| `superadmin@test.com` | `123456` | ✅ **Alternativo** - Usuário de teste criado |

### **Exemplo de Requisição:**
```json
{
  "email": "admin@admin.com",
  "password": "123456"
}
```

### **Resposta de Sucesso:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 4,
    "name": "Administrador master",
    "email": "admin@admin.com",
    "role": "super_admin",
    "permissions": [
      "manage_all_tenants",
      "create_tenants",
      "delete_tenants",
      "manage_tenant_users",
      "view_system_analytics",
      "manage_system_settings",
      "manage_billing",
      "manage_subscriptions",
      "view_logs",
      "manage_super_admins",
      "system_maintenance",
      "api_management"
    ]
  }
}
```

---

## 🏢 Tenant Users - Credenciais de Teste

### **Login de Usuário Tenant**
**Endpoint:** `POST /api/auth/login`

### **Tenant Demo - Credenciais:**
| Email | Senha | Role | Tenant |
|-------|-------|------|--------|
| `admin@demo.com` | `123456` | `admin` | `demo` |
| `manager@demo.com` | `123456` | `manager` | `demo` |

### **Headers Necessários:**
```
Content-Type: application/json
Accept: application/json
X-Tenant-Subdomain: demo
```

### **Exemplo de Requisição:**
```json
{
  "email": "admin@demo.com",
  "password": "123456"
}
```

---

## 🔑 Como Usar no Swagger

### **1. Fazer Login:**
1. Acesse a documentação Swagger
2. Encontre o endpoint `POST /api/super-admin/login`
3. Use as credenciais acima
4. Copie o `access_token` da resposta

### **2. Autorizar Requisições:**
1. Clique no botão **"Authorize"** no topo da página Swagger
2. No campo **"bearerAuth"**, digite: `Bearer {seu_access_token}`
3. Clique em **"Authorize"**
4. Agora você pode testar todos os endpoints protegidos

### **Exemplo de Authorization Header:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

---

## 📋 Endpoints Principais Documentados

### **Super Admin Profile:**
- ✅ `GET /api/super-admin/profile` - Exibir perfil
- ✅ `PUT /api/super-admin/profile` - Atualizar perfil
- ✅ `PUT /api/super-admin/profile/password` - Alterar senha
- ✅ `PUT /api/super-admin/profile/avatar` - Upload de avatar
- ✅ `DELETE /api/super-admin/profile/avatar` - Remover avatar
- ✅ `GET /api/super-admin/profile/preferences` - Obter preferências
- ✅ `PUT /api/super-admin/profile/preferences` - Atualizar preferências

### **Tenant User Profile:**
- ✅ `GET /api/profile` - Exibir perfil do usuário tenant
- ✅ `PUT /api/profile` - Atualizar perfil
- ✅ `PUT /api/profile/password` - Alterar senha
- ✅ `PUT /api/profile/avatar` - Upload de avatar
- ✅ `GET /api/profile/preferences` - Obter preferências

### **Gerenciamento de Tenants:**
- ✅ `GET /api/super-admin/tenants` - Listar tenants
- ✅ `POST /api/super-admin/tenants` - Criar tenant
- ✅ `GET /api/super-admin/tenants/{id}` - Exibir tenant
- ✅ `PUT /api/super-admin/tenants/{id}` - Atualizar tenant

### **Veículos e Configurações:**
- ✅ `GET /api/super-admin/tenants/{tenantId}/vehicles` - Listar veículos
- ✅ `POST /api/super-admin/tenants/{tenantId}/vehicles` - Criar veículo
- ✅ `GET /api/super-admin/tenants/{tenantId}/config` - Configurações do tenant

---

## ⚠️ Notas Importantes

### **Funcionalidades Temporariamente Desabilitadas:**
- **UserActivity**: Histórico de atividades (retorna coleção vazia)
- **UserSession**: Gerenciamento de sessões (retorna erro 501)

### **TODOs Pendentes:**
- Criar modelo `UserActivity` para registrar atividades
- Criar modelo `UserSession` para gerenciar sessões ativas
- Implementar funcionalidades completas quando os modelos estiverem prontos

### **Segurança:**
- ⚠️ **IMPORTANTE**: Estas são credenciais de desenvolvimento/teste
- 🔒 **Em produção**: Sempre usar senhas seguras e únicas
- 🔄 **Recomendação**: Alterar senhas regularmente

---

## 🚀 Status da Documentação

✅ **Swagger Gerado**: Documentação atualizada
✅ **Endpoints Funcionais**: Todos os endpoints principais testados
✅ **Autenticação**: JWT funcionando corretamente
✅ **Credenciais**: Disponíveis e testadas

**Última Atualização:** 24/08/2025 - 01:52 UTC
