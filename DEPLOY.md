# 🚀 Guia de Deploy - Portal Veículos SaaS

Este guia detalha como fazer o deploy da aplicação Portal Veículos SaaS em diferentes ambientes.

## 📋 Pré-requisitos

- Docker 20.10+ e Docker Compose 2.0+
- Git
- Para produção: Docker Swarm configurado
- Para produção: Portainer (opcional, mas recomendado)

## 🔧 Configuração Inicial

### 1. Configurar Variáveis de Ambiente

Primeiro, configure o arquivo `.env` com suas credenciais:

```bash
# Copiar arquivo de exemplo (já foi criado automaticamente)
cp .env.example .env

# Editar configurações
nano .env
```

### 2. Variáveis Essenciais

**Desenvolvimento:**
```env
DB_PASSWORD=sua_senha_mysql
REDIS_PASSWORD=sua_senha_redis
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
```

**Produção:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com
DB_PASSWORD=senha_forte_mysql
REDIS_PASSWORD=senha_forte_redis
MYSQL_ROOT_PASSWORD=senha_forte_root
LB_DOMAIN=seudominio.com
SSL_ACME_EMAIL=admin@seudominio.com
```

## 🛠️ Deploy para Desenvolvimento

O deploy de desenvolvimento usa Docker Compose padrão:

```bash
# Usar o script de deploy (recomendado)
./deploy.sh dev

# OU manualmente
docker-compose up -d --build
```

### Serviços Disponíveis (Desenvolvimento)

- **Aplicação Laravel**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8080
- **Redis Commander**: http://localhost:8081

## 🏭 Deploy para Produção

O deploy de produção usa Docker Swarm com alta disponibilidade:

### 1. Inicializar Docker Swarm

```bash
# No servidor manager
docker swarm init

# Nos servidores workers (opcional)
docker swarm join --token <token> <ip-manager>:2377
```

### 2. Criar Diretórios de Dados

```bash
sudo mkdir -p /data/{mysql,redis,app/storage,app/cache}
sudo chown -R 1000:1000 /data/app
```

### 3. Executar Deploy

```bash
# Usar o script de deploy (recomendado)
./deploy.sh prod

# OU manualmente
docker network create --driver overlay --attachable portal_veiculos_network
docker stack deploy -c docker-compose.production.yml portal-veiculos-saas
```

### Verificar Deploy

```bash
# Verificar serviços
docker service ls

# Verificar logs
docker service logs portal-veiculos-saas_app

# Ou usar o script
./deploy.sh logs prod
```

## 📊 Monitoramento (Produção)

A stack de produção inclui ferramentas de monitoramento:

- **Traefik Dashboard**: http://traefik.seudominio.com
- **Prometheus**: http://localhost:9090
- **Grafana**: http://localhost:3000 (admin:admin123)

## 💾 Backup e Restauração

### Criar Backup

```bash
# Usar o script
./deploy.sh backup

# OU manualmente (desenvolvimento)
docker-compose exec mysql mysqldump -u root -p[senha] portal_veiculos_saas > backup.sql
```

### Restaurar Backup

```bash
# Usar o script
./deploy.sh restore ./backups/backup_20231201_120000.sql

# OU manualmente (desenvolvimento)
docker-compose exec mysql mysql -u root -p[senha] portal_veiculos_saas < backup.sql
```

## 🔧 Comandos Úteis

### Desenvolvimento

```bash
# Rebuild completo
docker-compose down && docker-compose up -d --build

# Logs da aplicação
docker-compose logs -f app

# Executar artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear

# Parar tudo
docker-compose down
```

### Produção

```bash
# Atualizar stack
docker stack deploy -c docker-compose.production.yml portal-veiculos-saas

# Escalar serviços
docker service scale portal-veiculos-saas_app=5

# Ver status dos serviços
docker service ps portal-veiculos-saas_app

# Remover stack
docker stack rm portal-veiculos-saas
```

## 🚨 Solução de Problemas

### Erro: "failed to solve: process did not complete successfully"

**Problema**: Arquivos `.env` ou `.env.example` não existem.
**Solução**: Os arquivos foram criados automaticamente. Configure as variáveis e tente novamente.

### Erro: "network not found"

**Problema**: Network overlay não existe.
**Solução**:
```bash
docker network create --driver overlay --attachable portal_veiculos_network
```

### Erro: "cannot connect to database"

**Problema**: MySQL não está pronto.
**Solução**: Aguarde alguns segundos para o MySQL inicializar ou verifique as credenciais no `.env`.

### Erro de permissões no storage

**Problema**: Laravel não consegue escrever nos diretórios.
**Solução**:
```bash
# Desenvolvimento
docker-compose exec app chown -R apache:apache /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage

# Produção
sudo chown -R 1000:1000 /data/app
sudo chmod -R 775 /data/app
```

## 🔄 Pipeline CI/CD

O arquivo `.github/workflows/docker.yml` já está configurado para build automático. Para deploy automático, adicione secrets no GitHub:

- `DOCKER_USERNAME`: Usuário do Docker Hub
- `DOCKER_PASSWORD`: Senha do Docker Hub

## 📚 Recursos Adicionais

- [Documentação Laravel](https://laravel.com/docs)
- [Docker Swarm](https://docs.docker.com/engine/swarm/)
- [Traefik](https://doc.traefik.io/traefik/)
- [Portainer](https://documentation.portainer.io/)

## 🆘 Suporte

Em caso de problemas:

1. Verifique os logs: `./deploy.sh logs`
2. Verifique o status dos serviços: `docker service ls`
3. Verifique as configurações do `.env`
4. Consulte a documentação oficial das ferramentas

---

**Desenvolvido para Portal Veículos SaaS** 🚗
