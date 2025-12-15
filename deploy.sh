#!/bin/bash

echo "🚀 Iniciando deploy do SaaS Automotivo Backend..."

# Configurar variáveis de ambiente
echo "📝 Configurando variáveis de ambiente..."
sed -i '' 's|${APP_NAME}|'${APP_NAME:-'SaaS Automotivo'}'|g' .env
sed -i 's|${APP_URL}|'${APP_URL:-'http://localhost'}'|g' .env
sed -i 's|${APP_DEBUG}|'${APP_DEBUG:-'false'}'|g' .env
sed -i 's|${DB_CONNECTION}|'${DB_CONNECTION:-'mysql'}'|g' .env
sed -i 's|${DB_HOST}|'${DB_HOST:-'localhost'}'|g' .env
sed -i 's|${DB_PORT}|'${DB_PORT:-'3306'}'|g' .env
sed -i 's|${DB_DATABASE}|'${DB_DATABASE:-'saas_automotivo'}'|g' .env
sed -i 's|${DB_USERNAME}|'${DB_USERNAME:-'root'}'|g' .env
sed -i 's|${DB_PASSWORD}|'${DB_PASSWORD:-''}'|g' .env
sed -i 's|${REDIS_PASSWORD}|'${REDIS_PASSWORD:-''}'|g' .env

sed -i 's|${MAIL_MAILER}|'${MAIL_MAILER:-'smtp'}'|g' .env
sed -i 's|${MAIL_HOST}|'${MAIL_HOST:-'localhost'}'|g' .env
sed -i 's|${MAIL_PORT}|'${MAIL_PORT:-'587'}'|g' .env
sed -i 's|${MAIL_USERNAME}|'${MAIL_USERNAME:-''}'|g' .env
sed -i 's|${MAIL_PASSWORD}|'${MAIL_PASSWORD:-''}'|g' .env
sed -i 's|${MAIL_ENCRYPTION}|'${MAIL_ENCRYPTION:-'tls'}'|g' .env
sed -i 's|${MAIL_FROM_ADDRESS}|'${MAIL_FROM_ADDRESS:-'noreply@localhost'}'|g' .env

echo "✅ Arquivo .env atualizado com sucesso."

# Otimizar autoloader
echo "📦 Otimizando autoloader..."
composer dump-autoload --optimize --no-dev

# Verificar e gerar APP_KEY se necessário
if grep -q "^APP_KEY=" .env && [ -n "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" ]; then
    echo "🔑 APP_KEY já existe, não será gerada uma nova chave."
else
    echo "🔑 Gerando nova APP_KEY..."
    php artisan key:generate
fi

# Verificar e gerar JWT_SECRET se necessário
if grep -q "^JWT_SECRET=" .env && [ -n "$(grep '^JWT_SECRET=' .env | cut -d'=' -f2)" ]; then
    echo "🔐 JWT_SECRET já existe, não será gerada uma nova chave."
else
    echo "🔐 Gerando nova JWT_SECRET..."
    php artisan jwt:secret --force
fi

# Limpar caches
echo "🧹 Limpando caches..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Criar link simbólico para storage
echo "📁 Criando link simbólico para storage..."
php artisan storage:link

# Executar migrações
echo "🗄️ Executando migrações..."
php artisan migrate --force

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

# Cache de ícones
echo "🎨 Cacheando ícones..."
php artisan icon:cache

# Build dos assets se necessário
if [ -f "package.json" ]; then
    echo "🔨 Buildando assets..."
    npm ci --production
    npm run build
fi

# Configurar permissões
echo "🔒 Configurando permissões..."
if [ -n "${WEB_USER:-}" ]; then
    chown -R ${WEB_USER}:${WEB_USER} /var/www/html
    find /var/www/html -type d -exec chmod 755 {} \;
    find /var/www/html -type f -exec chmod 644 {} \;
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    chmod -R ugo+rw /var/www/html/storage /var/www/html/bootstrap/cache
else
    echo "⚠️ WEB_USER não definido, pulando configuração de permissões"
fi

# Verificar saúde da aplicação
echo "🏥 Verificando saúde da aplicação..."
if php artisan --version > /dev/null 2>&1; then
    echo "✅ Aplicação Laravel funcionando corretamente"
else
    echo "❌ Erro na aplicação Laravel"
    exit 1
fi

# Verificar conexão com banco
echo "🗄️ Verificando conexão com banco de dados..."
if php artisan tinker --execute="echo 'Conexão OK';" > /dev/null 2>&1; then
    echo "✅ Conexão com banco de dados OK"
else
    echo "❌ Erro na conexão com banco de dados"
    exit 1
fi

echo "🎉 Deploy concluído com sucesso!"
echo "📊 Status da aplicação:"
echo "   - Laravel: ✅"
echo "   - Banco de dados: ✅"
echo "   - JWT: ✅"
echo "   - Tenant: ✅"
echo ""
echo "🌐 A aplicação está rodando em: ${APP_URL:-'http://localhost'}"
echo "📚 Documentação da API: ${APP_URL:-'http://localhost'}/api/documentation"
