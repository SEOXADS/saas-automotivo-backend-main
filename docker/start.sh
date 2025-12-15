#!/bin/bash

echo "🚀 Iniciando SaaS Automotivo Backend no Docker..."

# Criar diretórios necessários se não existirem
echo "📁 Criando diretórios necessários..."
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Configurar variáveis de ambiente se não existirem
if [ -z "$APP_KEY" ]; then
    echo "🔑 Gerando APP_KEY..."
    php artisan key:generate
fi

if [ -z "$JWT_SECRET" ]; then
    echo "🔐 Gerando JWT_SECRET..."
    php artisan jwt:secret --force
fi

# Limpar caches
echo "🧹 Limpando caches..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Criar link simbólico para storage se não existir
if [ ! -L "public/storage" ]; then
    echo "📁 Criando link simbólico para storage..."
    php artisan storage:link
fi

# Executar migrações se necessário
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "🗄️ Executando migrações..."
    php artisan migrate --force
fi

# Executar seeders se necessário
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "🌱 Executando seeders..."
    php artisan db:seed --force
fi

# Otimizar aplicação
echo "⚡ Otimizando aplicação..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permissões
echo "🔒 Configurando permissões..."
chown -R apache:apache /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Verificar configuração do Apache
echo "🌐 Verificando configuração do Apache..."
httpd -t

if [ $? -eq 0 ]; then
    echo "✅ Configuração do Apache válida"
else
    echo "❌ Erro na configuração do Apache"
    exit 1
fi

# Verificar saúde da aplicação
echo "🏥 Verificando saúde da aplicação..."
if php artisan --version > /dev/null 2>&1; then
    echo "✅ Aplicação Laravel funcionando corretamente"
else
    echo "❌ Erro na aplicação Laravel"
    exit 1
fi

echo "🎉 Aplicação pronta! Iniciando Apache..."

# Iniciar Apache em primeiro plano
exec /usr/sbin/httpd -D FOREGROUND
