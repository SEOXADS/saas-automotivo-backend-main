# Use a imagem base do Rocky Linux 8
FROM rockylinux/rockylinux:8

# Definir variáveis de ambiente
ENV TZ=America/Sao_Paulo
ENV COMPOSER_ALLOW_SUPERUSER=1

# Atualiza o sistema operacional
RUN dnf -y update && dnf clean all

# Instalar EPEL
RUN dnf -y install epel-release && dnf clean all

# Ferramentas necessárias
RUN dnf -y install nano wget curl iputils net-tools vim glances htop unzip && \
    dnf clean all

# Instala os compiladores
RUN dnf install gcc-c++ make git curl -y
RUN dnf install git -y
RUN dnf clean all

# Adicionar repositório Remi para PHP
RUN dnf -y install https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Resetar e definir a versão do PHP
RUN dnf module reset php -y && dnf module enable php:remi-8.3 -y

# Instalar dependências do sistema e PHP
RUN dnf -y install \
    php php-cli php-fpm php-mysqlnd php-zip php-devel php-gd php-mbstring \
    php-curl php-xml php-pear php-bcmath php-json php-intl php-xdebug php-pcov \
    httpd httpd-devel mod_ssl gcc-c++ make git curl nano wget iputils net-tools vim redis glances htop && \
    dnf clean all
RUN dnf install php-redis -y
# Configurar PHP
RUN mkdir -p /var/run/php-fpm && \
    sed -i 's#^date.timezone =.*#date.timezone = America/Sao_Paulo#' /etc/php.ini && \
    sed -i 's#^cgi.fix_pathinfo=.*#cgi.fix_pathinfo=1#' /etc/php.ini && \
    sed -i 's/memory_limit = .*/memory_limit = 2048M/' /etc/php.ini && \
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php.ini && \
    sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php.ini && \
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php.ini && \
    sed -i 's/max_input_time = .*/max_input_time = 300/' /etc/php.ini && \
    sed -i 's/;max_input_vars = .*/max_input_vars = 10000/' /etc/php.ini && \
    sed -i 's/;opcache.enable=.*$/opcache.enable=1/' /etc/php.ini && \
    sed -i 's/;opcache.memory_consumption=.*$/opcache.memory_consumption=128/' /etc/php.ini && \
    sed -i 's/;opcache.max_accelerated_files=.*$/opcache.max_accelerated_files=10000/' /etc/php.ini && \
    sed -i 's/;opcache.revalidate_freq=.*$/opcache.revalidate_freq=2/' /etc/php.ini

# Instalar Composer
RUN dnf install composer -y && dnf clean all

# Adicionar repositório Node.js e instalar Node.js
RUN curl -fsSL https://rpm.nodesource.com/setup_18.x | bash - && \
    dnf install -y nodejs && dnf clean all

# Configurar Apache
RUN rm -f /etc/httpd/conf.d/welcome.conf /etc/httpd/conf.d/ssl.conf && \
    echo "ServerName localhost" >> /etc/httpd/conf/httpd.conf

# Copiar o projeto Laravel para a pasta padrão do Apache
COPY . /var/www/html
WORKDIR /var/www/html

# Copiar a configuração do Apache para o contêiner
COPY laravel.conf /etc/httpd/conf.d/laravel.conf

# Instalar dependências do Laravel
RUN composer install --no-scripts --no-autoloader
RUN npm install

# Criar o arquivo .env a partir do .env.example
RUN cp .env.example .env

# Configurar permissões
RUN chown -R apache:apache /var/www/html

# Criar diretórios necessários se não existirem
RUN mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache

# Definir permissões para diretórios
RUN find /var/www/html -type d -exec chmod 755 {} \;

# Definir permissões para arquivos
RUN find /var/www/html -type f -exec chmod 644 {} \;

# Definir permissões especiais para storage e cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R ugo+rw /var/www/html/storage /var/www/html/bootstrap/cache

# Definir permissões para executáveis
RUN chmod -R 775 /var/www/html/node_modules/.bin/*

RUN chmod -R 775 /var/www/html/node_modules/@esbuild/linux-x64/bin/*

RUN chmod +x /var/www/html/start.sh

# Expor porta 80
EXPOSE 80
EXPOSE 443

# Iniciar PHP-FPM e Apache
CMD ["/bin/sh", "-c", "php-fpm -D && /var/www/html/start.sh && php artisan queue:work --daemon --sleep=3 --tries=1 --queue=high,medium,low,default,url & /usr/sbin/httpd -D FOREGROUND"]
