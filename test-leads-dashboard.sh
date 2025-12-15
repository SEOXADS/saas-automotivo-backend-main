#!/bin/bash

echo "🧪 Testando rota de leads/dashboard"
echo "==================================="

# URL da API
API_URL="https://www.api.webcarros.app.br"

# Token JWT (substitua pelo token válido)
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vd3d3LmFwaS53ZWJjYXJyb3MuYXBwLmJyL2FwaS9hdXRoL2xvZ2luIiwiaWF0IjoxNzU1MjI3MTUwLCJleHAiOjE3NTUyMzA3NTAsIm5iZiI6MTc1NTIyNzE1MCwianRpIjoiZWRNMTRYSEhrdFZDZ2RyVyIsInN1YiI6IjEwIiwicHJ2IjoiNzQ3OTdiMjJjN2NiODEzODQ0ZGYyYjU4YzhjMmIyOWNhYmIwNjc1NyIsInRlbmFudF9pZCI6Mywicm9sZSI6ImFkbWluIn0.zzxDJYGv5QFp5rKVoa5rVE7R6HrSvEtZ853tzTFjENQ"

echo ""
echo "🔐 Testando com token JWT válido"
echo "--------------------------------"

# Teste da rota leads/dashboard
curl -X GET "$API_URL/api/leads/dashboard" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Origin: https://saas-automotivo-client.vercel.app" \
  -H "X-Tenant-Subdomain: seopara" \
  -H "Accept: */*" \
  -v

echo ""
echo "📊 Verificando headers da resposta"
echo "----------------------------------"

# Verificar headers
curl -I "$API_URL/api/leads/dashboard" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Origin: https://saas-automotivo-client.vercel.app" \
  -H "X-Tenant-Subdomain: seopara" \
  -v

echo ""
echo "🔍 Testando rota de autenticação"
echo "--------------------------------"

# Testar rota de autenticação
curl -X GET "$API_URL/api/auth/me" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Origin: https://saas-automotivo-client.vercel.app" \
  -H "X-Tenant-Subdomain: seopara" \
  -v

echo ""
echo "✅ Teste concluído!"
echo ""
echo "📝 Para verificar logs, execute:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "🚨 Se ainda der erro 401, verifique:"
echo "   1. Se o token JWT é válido"
echo "   2. Se o usuário existe e está ativo"
echo "   3. Se o tenant está ativo"
echo "   4. Se os middlewares estão funcionando"
echo "   5. Se há conflitos entre middlewares"
