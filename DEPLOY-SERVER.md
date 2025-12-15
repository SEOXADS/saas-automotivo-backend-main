# 🚀 Guia de Deploy para Servidor - SaaS Automotivo Backend

## 📋 Pré-requisitos

### **Servidor**
- **Sistema Operacional**: Ubuntu 20.04+ / CentOS 7+ / Debian 10+
- **PHP**: 8.1+ com extensões:
  - `php-mysql`
  - `php-redis` (opcional)
  - `php-gd`
  - `php-mbstring`
  - `php-xml`
  - `php-curl`
  - `php-zip`
  - `php-opcache`
- **MySQL/MariaDB**: 8.0+ ou 10.5+
- **Nginx/Apache**: Configurado para PHP
- **Composer**: 2.0+
- **Node.js**: 16+ (para build de assets)
- **Git**: Para clonar o repositório

### **Banco de Dados**
```sql
-- Criar banco de dados
CREATE DATABASE saas_automotivo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário
CREATE USER 'saas_user'@'localhost' IDENTIFIED BY 'sua_senha_segura_aqui';

-- Conceder permissões
GRANT ALL PRIVILEGES ON saas_automotivo.* TO 'saas_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🔧 Instalação

### **1. Clonar o Repositório**
```bash
cd /var/www
git clone https://github.com/seu-usuario/saas-automotivo-backend.git
cd saas-automotivo-backend
```

### **2. Configurar Variáveis de Ambiente**
```bash
# Copiar arquivo de exemplo
cp .env.server .env

# Editar configurações
nano .env
```

**Configurações Obrigatórias:**
```env
APP_NAME="SaaS Automotivo"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=saas_automotivo
DB_USERNAME=saas_user
DB_PASSWORD=sua_senha_segura_aqui

JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### **3. Instalar Dependências**
```bash
# Instalar dependências PHP
composer install --no-dev --optimize-autoloader

# Instalar dependências Node.js (se necessário)
npm ci --production
```

### **4. Configurar Permissões**
```bash
# Definir usuário do servidor web
export WEB_USER=www-data  # Para Ubuntu/Debian
# export WEB_USER=apache   # Para CentOS/RHEL

# Configurar permissões
sudo chown -R $WEB_USER:$WEB_USER /var/www/saas-automotivo-backend
sudo chmod -R 755 /var/www/saas-automotivo-backend
sudo chmod -R 775 /var/www/saas-automotivo-backend/storage
sudo chmod -R 775 /var/www/saas-automotivo-backend/bootstrap/cache
```

### **5. Executar Script de Deploy**
```bash
# Tornar executável
chmod +x deploy.sh

# Executar deploy
./deploy.sh
```

## 🌐 Configuração do Servidor Web

### **Nginx (Recomendado)**

**Arquivo: `/etc/nginx/sites-available/saas-automotivo`**
```nginx
server {
    listen 80;
    server_name seudominio.com www.seudominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seudominio.com www.seudominio.com;

    # SSL
    ssl_certificate /etc/letsencrypt/live/seudominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seudominio.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Document root
    root /var/www/saas-automotivo-backend/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/saas-automotivo.access.log;
    error_log /var/log/nginx/saas-automotivo.error.log;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # CORS headers
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Authorization, Content-Type, X-Requested-With, Accept, Origin, X-Tenant-Subdomain" always;

    # Handle preflight requests
    if ($request_method = 'OPTIONS') {
        add_header Access-Control-Allow-Origin "*";
        add_header Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS";
        add_header Access-Control-Allow-Headers "Authorization, Content-Type, X-Requested-With, Accept, Origin, X-Tenant-Subdomain";
        add_header Access-Control-Max-Age 1728000;
        add_header Content-Type "text/plain; charset=utf-8";
        add_header Content-Length 0;
        return 204;
    }

    # API routes
    location /api {
        try_files $uri $uri/ /index.php?$query_string;

        # PHP processing
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Public routes
    location /public {
        try_files $uri $uri/ /index.php?$query_string;

        # PHP processing
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Storage files
    location /storage {
        alias /var/www/saas-automotivo-backend/storage/app/public;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Main application
    location / {
        try_files $uri $uri/ /index.php?$query_string;

        # PHP processing
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ \.(env|log|sql|md|yml|yaml|lock|git)$ {
        deny all;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Security
        fastcgi_hide_header X-Powered-By;
        fastcgi_param HTTP_PROXY "";
    }
}
```

**Ativar o site:**
```bash
sudo ln -s /etc/nginx/sites-available/saas-automotivo /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### **Apache (Alternativa)**

**Arquivo: `/etc/apache2/sites-available/saas-automotivo.conf`**
```apache
<VirtualHost *:80>
    ServerName seudominio.com
    ServerAlias www.seudominio.com
    DocumentRoot /var/www/saas-automotivo-backend/public

    <Directory /var/www/saas-automotivo-backend/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/saas-automotivo_error.log
    CustomLog ${APACHE_LOG_DIR}/saas-automotivo_access.log combined

    # CORS headers
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Authorization, Content-Type, X-Requested-With, Accept, Origin, X-Tenant-Subdomain"

    # Handle preflight requests
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</VirtualHost>
```

**Ativar o site:**
```bash
sudo a2ensite saas-automotivo
sudo a2enmod rewrite headers
sudo systemctl reload apache2
```

## 🔐 Configuração SSL (Let's Encrypt)

### **Instalar Certbot**
```bash
# Ubuntu/Debian
sudo apt install certbot python3-certbot-nginx

# CentOS/RHEL
sudo yum install certbot python3-certbot-nginx
```

### **Gerar Certificado**
```bash
sudo certbot --nginx -d seudominio.com -d www.seudominio.com
```

### **Renovação Automática**
```bash
# Adicionar ao crontab
sudo crontab -e

# Adicionar linha:
0 12 * * * /usr/bin/certbot renew --quiet
```

## 📊 Monitoramento e Logs

### **Logs da Aplicação**
```bash
# Logs do Laravel
tail -f /var/www/saas-automotivo-backend/storage/logs/laravel.log

# Logs do Nginx
tail -f /var/log/nginx/saas-automotivo.error.log

# Logs do PHP-FPM
tail -f /var/log/php8.1-fpm.log
```

### **Status dos Serviços**
```bash
# Verificar status
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql

# Reiniciar serviços
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
```

## 🔄 Deploy Automatizado

### **Script de Deploy**
```bash
#!/bin/bash
# deploy-auto.sh

cd /var/www/saas-automotivo-backend

# Pull das alterações
git pull origin main

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Limpar caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Executar migrações
php artisan migrate --force

# Configurar permissões
sudo chown -R www-data:www-data /var/www/saas-automotivo-backend
sudo chmod -R 775 /var/www/saas-automotivo-backend/storage

echo "Deploy concluído em $(date)"
```

### **Crontab para Deploy Automático**
```bash
# Editar crontab
crontab -e

# Deploy a cada 5 minutos (desenvolvimento)
*/5 * * * * /var/www/saas-automotivo-backend/deploy-auto.sh

# Deploy diário (produção)
0 2 * * * /var/www/saas-automotivo-backend/deploy-auto.sh
```

## 🚨 Troubleshooting

### **Problemas Comuns**

#### **1. Erro 500 - Internal Server Error**
```bash
# Verificar logs
tail -f /var/www/saas-automotivo-backend/storage/logs/laravel.log

# Verificar permissões
ls -la /var/www/saas-automotivo-backend/storage/
ls -la /var/www/saas-automotivo-backend/bootstrap/cache/

# Corrigir permissões
sudo chown -R www-data:www-data /var/www/saas-automotivo-backend
sudo chmod -R 775 /var/www/saas-automotivo-backend/storage
```

#### **2. Erro de Conexão com Banco**
```bash
# Testar conexão
php artisan tinker --execute="echo 'Conexão OK';"

# Verificar configurações
php artisan config:show database

# Verificar se o MySQL está rodando
sudo systemctl status mysql
```

#### **3. Erro JWT**
```bash
# Verificar JWT_SECRET
php artisan tinker --execute="echo config('jwt.secret') ? 'OK' : 'NÃO CONFIGURADO';"

# Gerar nova chave JWT
php artisan jwt:secret --force
```

#### **4. Erro de Tenant**
```bash
# Verificar se o tenant existe
php artisan tinker --execute="echo App\Models\Tenant::count();"

# Verificar se o usuário existe
php artisan tinker --execute="echo App\Models\TenantUser::count();"
```

### **Comandos Úteis**
```bash
# Limpar todos os caches
php artisan optimize:clear

# Recarregar configurações
php artisan config:cache

# Verificar rotas
php artisan route:list

# Verificar status da aplicação
php artisan about

# Verificar permissões de storage
php artisan storage:link
```

## 📈 Performance

### **Opcache**
```ini
; /etc/php/8.1/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
```

### **Redis (Opcional)**
```bash
# Instalar Redis
sudo apt install redis-server

# Configurar Laravel para usar Redis
# Alterar no .env:
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🔒 Segurança

### **Firewall**
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# iptables (CentOS)
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
```

### **Fail2ban**
```bash
# Instalar
sudo apt install fail2ban

# Configurar para Nginx
sudo nano /etc/fail2ban/jail.local

[nginx-http-auth]
enabled = true
port = http,https
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 3
bantime = 3600
```

## 📞 Suporte

### **Informações Úteis**
- **Versão do Laravel**: `php artisan --version`
- **Versão do PHP**: `php -v`
- **Versão do MySQL**: `mysql --version`
- **Status dos serviços**: `sudo systemctl status nginx php8.1-fpm mysql`

### **Logs Importantes**
- **Aplicação**: `/var/www/saas-automotivo-backend/storage/logs/laravel.log`
- **Nginx**: `/var/log/nginx/saas-automotivo.error.log`
- **PHP-FPM**: `/var/log/php8.1-fpm.log`
- **MySQL**: `/var/log/mysql/error.log`

---

## 🎯 Checklist de Deploy

- [ ] Repositório clonado
- [ ] Arquivo .env configurado
- [ ] Dependências instaladas
- [ ] Permissões configuradas
- [ ] Script de deploy executado
- [ ] Servidor web configurado
- [ ] SSL configurado
- [ ] Banco de dados configurado
- [ ] Migrações executadas
- [ ] Caches otimizados
- [ ] Logs funcionando
- [ ] Monitoramento configurado
- [ ] Backup configurado
- [ ] Segurança configurada

---

**🎉 Parabéns! Seu SaaS Automotivo está rodando em produção!**
