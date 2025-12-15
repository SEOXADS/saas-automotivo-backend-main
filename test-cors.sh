#!/bin/bash

echo "🧪 Testando CORS para SaaS Automotivo Backend"
echo "=============================================="

# URLs dos domínios Vercel
CLIENT_URL="https://saas-automotivo-client.vercel.app"
ADMIN_URL="https://saas-automotivo-admin.vercel.app"

# URL da API (ajuste conforme necessário)
API_URL="http://localhost:8000"

echo ""
echo "🌐 Testando preflight OPTIONS para $CLIENT_URL"
echo "----------------------------------------------"

# Teste de preflight para o cliente
curl -X OPTIONS "$API_URL/api/auth/login" \
  -H "Origin: $CLIENT_URL" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, X-Tenant-Subdomain" \
  -v

echo ""
echo "🌐 Testando preflight OPTIONS para $ADMIN_URL"
echo "----------------------------------------------"

# Teste de preflight para o admin
curl -X OPTIONS "$API_URL/api/auth/login" \
  -H "Origin: $ADMIN_URL" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, X-Tenant-Subdomain" \
  -v

echo ""
echo "🔐 Testando login com CORS para $CLIENT_URL"
echo "-------------------------------------------"

# Teste de login para o cliente
curl -X POST "$API_URL/api/auth/login" \
  -H "Origin: $CLIENT_URL" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Subdomain: demo" \
  -d '{
    "email": "admin@demo.com",
    "password": "password123",
    "tenant_subdomain": "demo"
  }' \
  -v

echo ""
echo "🔐 Testando login com CORS para $ADMIN_URL"
echo "-------------------------------------------"

# Teste de login para o admin
curl -X POST "$API_URL/api/auth/login" \
  -H "Origin: $ADMIN_URL" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Subdomain: demo" \
  -d '{
    "email": "admin@demo.com",
    "password": "password123",
    "tenant_subdomain": "demo"
  }' \
  -v

echo ""
echo "📊 Verificando headers CORS na resposta"
echo "---------------------------------------"

# Verificar headers CORS
curl -I "$API_URL/api/auth/login" \
  -H "Origin: $CLIENT_URL" \
  -v

echo ""
echo "✅ Teste de CORS concluído!"
echo ""
echo "📝 Para verificar se está funcionando, procure por:"
echo "   - Access-Control-Allow-Origin: $CLIENT_URL ou $ADMIN_URL"
echo "   - Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS"
echo "   - Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, Accept, Origin, X-Tenant-Subdomain"
echo "   - Access-Control-Allow-Credentials: true"
echo ""
echo "🚨 Se houver erro 403, verifique:"
echo "   1. Se o container está rodando"
echo "   2. Se as configurações CORS estão aplicadas"
echo "   3. Se o Apache/Nginx está configurado corretamente"
echo "   4. Se os módulos necessários estão habilitados"
