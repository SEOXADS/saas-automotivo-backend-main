#!/bin/bash

# Script para criar usuário admin do SaaS
# Uso: ./setup-admin.sh

echo "🚀 Portal Veículos SaaS - Configuração de Administrador"
echo "=================================================="
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do Laravel"
    exit 1
fi

# Verificar se o PHP existe
if ! command -v php &> /dev/null; then
    echo "❌ Erro: PHP não está instalado ou não está no PATH"
    exit 1
fi

echo "📋 Insira os dados do administrador:"
echo ""

# Solicitar dados do tenant
read -p "🏢 Nome da empresa/tenant: " TENANT_NAME
read -p "🌐 Subdomínio (ex: minhaempresa): " TENANT_SUBDOMAIN
read -p "📧 Email do tenant: " TENANT_EMAIL
read -p "📞 Telefone do tenant: " TENANT_PHONE

echo ""
echo "📋 Dados do administrador:"

# Solicitar dados do admin
read -p "👤 Nome do administrador: " ADMIN_NAME
read -p "📧 Email do administrador: " ADMIN_EMAIL

# Solicitar senha com confirmação
while true; do
    read -s -p "🔑 Senha do administrador: " ADMIN_PASSWORD
    echo ""
    read -s -p "🔑 Confirme a senha: " ADMIN_PASSWORD_CONFIRM
    echo ""

    if [ "$ADMIN_PASSWORD" = "$ADMIN_PASSWORD_CONFIRM" ]; then
        break
    else
        echo "❌ Senhas não conferem. Tente novamente."
        echo ""
    fi
done

read -p "📞 Telefone do administrador (opcional): " ADMIN_PHONE

echo ""
echo "🔄 Criando usuário administrador..."

# Executar comando artisan
php artisan admin:create \
    --name="$ADMIN_NAME" \
    --email="$ADMIN_EMAIL" \
    --password="$ADMIN_PASSWORD" \
    --tenant="$TENANT_SUBDOMAIN" \
    --phone="$ADMIN_PHONE"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Administrador criado com sucesso!"
    echo ""
    echo "🔐 CREDENCIAIS DE ACESSO:"
    echo "📧 Email: $ADMIN_EMAIL"
    echo "🔑 Senha: $ADMIN_PASSWORD"
    echo "🏢 Tenant: $TENANT_SUBDOMAIN"
    echo "🌐 URL: $(php artisan tinker --execute='echo config("app.url");')"
    echo ""
    echo "⚠️  IMPORTANTE: Salve essas credenciais em local seguro!"
else
    echo "❌ Erro ao criar administrador. Verifique os logs acima."
    exit 1
fi
