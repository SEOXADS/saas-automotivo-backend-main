#!/bin/bash

sed -i 's|${APP_NAME}|'${APP_NAME}'|g' .env
sed -i 's|${APP_URL}|'${APP_URL}'|g' .env
sed -i 's|${APP_DEBUG}|'${APP_DEBUG}'|g' .env
sed -i 's|${DB_CONNECTION}|'${DB_CONNECTION}'|g' .env
sed -i 's|${DB_HOST}|'${DB_HOST}'|g' .env
sed -i 's|${DB_PORT}|'${DB_PORT}'|g' .env
sed -i 's|${DB_DATABASE}|'${DB_DATABASE}'|g' .env
sed -i 's|${DB_USERNAME}|'${DB_USERNAME}'|g' .env
sed -i 's|${DB_PASSWORD}|'${DB_PASSWORD}'|g' .env
sed -i 's|${REDIS_PASSWORD}|'${REDIS_PASSWORD}'|g' .env

sed -i 's|${MAIL_MAILER}|'${MAIL_MAILER}'|g' .env
sed -i 's|${MAIL_HOST}|'${MAIL_HOST}'|g' .env
sed -i 's|${MAIL_PORT}|'${MAIL_PORT}'|g' .env
sed -i 's|${MAIL_USERNAME}|'${MAIL_USERNAME}'|g' .env
sed -i 's|${MAIL_PASSWORD}|'${MAIL_PASSWORD}'|g' .env
sed -i 's|${MAIL_ENCRYPTION}|'${MAIL_ENCRYPTION}'|g' .env
sed -i 's|${MAIL_FROM_ADDRESS}|'${MAIL_FROM_ADDRESS}'|g' .env

echo "Arquivo .env atualizado com sucesso."

composer dump-autoload --optimize
# Verifica se já existe uma APP_KEY no .env
if grep -q "^APP_KEY=" .env && [ -n "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" ]; then
    echo "APP_KEY já existe, não será gerada uma nova chave."
else
    echo "Gerando nova APP_KEY..."

    php artisan key:generate

    php artisan optimize:clear
fi


php artisan storage:link

# Criar links simbólicos para robots.txt e sitemaps
echo "Criando links simbólicos para robots.txt e sitemaps..."
ln -sf ../storage/app/robots public/robots
ln -sf ../storage/app/sitemaps public/sitemaps

# Criar diretórios necessários com permissões corretas
echo "Criando diretórios de storage com permissões corretas..."
mkdir -p storage/app/robots
mkdir -p storage/app/sitemaps
chmod 755 storage/app/robots
chmod 755 storage/app/sitemaps

# Criar arquivos .gitkeep para manter os diretórios no git
touch storage/app/robots/.gitkeep
touch storage/app/sitemaps/.gitkeep

# Verificar se os links simbólicos foram criados corretamente
echo "Verificando links simbólicos..."
if [ -L "public/robots" ]; then
    echo "✅ Link simbólico public/robots criado com sucesso"
else
    echo "❌ Erro: Link simbólico public/robots não foi criado"
fi

if [ -L "public/sitemaps" ]; then
    echo "✅ Link simbólico public/sitemaps criado com sucesso"
else
    echo "❌ Erro: Link simbólico public/sitemaps não foi criado"
fi

php artisan migrate --force

php artisan db:seed


php artisan optimize

php artisan icon:cache

npm run build


chown -R apache:apache /var/www/html

find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R ugo+rw /var/www/html/storage /var/www/html/bootstrap/cache

# Permissões específicas para robots e sitemaps
echo "Configurando permissões específicas para robots e sitemaps..."
chmod -R 775 /var/www/html/storage/app/robots
chmod -R 775 /var/www/html/storage/app/sitemaps
chmod -R ugo+rw /var/www/html/storage/app/robots
chmod -R ugo+rw /var/www/html/storage/app/sitemaps

chmod -R 775 /var/www/html/node_modules/.bin/*

chmod -R 775 /var/www/html/node_modules/@esbuild/linux-x64/bin/*
