# 📁 Estrutura de Armazenamento de Sitemaps

## 🎯 **Localização Persistente**

Os sitemaps são agora armazenados de forma **persistente** no diretório `storage/app/sitemaps/` para garantir que os arquivos não sejam perdidos em deployments ou reinicializações do servidor.

## 📂 **Estrutura de Diretórios**

```
storage/app/sitemaps/
├── .gitkeep                           # Mantém o diretório no Git
├── omegaveiculos/                     # Tenant específico
│   ├── sitemap.xml                    # Sitemap geral
│   ├── sitemap-vehicle_detail.xml     # Sitemap de veículos
│   ├── sitemap-collection.xml         # Sitemap de coleções
│   ├── sitemap-blog_post.xml          # Sitemap de posts
│   ├── sitemap-faq.xml                # Sitemap de FAQ
│   └── sitemap-static.xml             # Sitemap de páginas estáticas
└── outroteant/                        # Outro tenant
    └── ...
```

## 🔗 **Acesso Público**

Para manter a compatibilidade com URLs públicas, foi criado um **link simbólico**:

```bash
public/sitemaps -> ../storage/app/sitemaps
```

Isso permite que os sitemaps sejam acessados via:
- `https://domain.com/sitemaps/tenant/sitemap.xml`
- `https://domain.com/sitemaps/tenant/sitemap-vehicle_detail.xml`

## 🚀 **Endpoints Disponíveis**

### 1. **Gerar Sitemap (Super Admin)**
```bash
GET /api/super-admin/seo/sitemap?tenant=omegaveiculos&type=vehicle_detail
```
- **Acesso exclusivo Super Admin** (requer autenticação)
- Gera e salva o sitemap no storage
- Retorna XML ou JSON conforme solicitado

### 2. **Gerar Sitemap Index**
```bash
GET /api/seo/sitemap-index?tenant=omegaveiculos
```
- Gera o sitemap index com todos os sitemaps do tenant
- Lista apenas arquivos existentes no storage

### 3. **Servir Arquivo de Sitemap**
```bash
GET /api/seo/sitemap-file?tenant=omegaveiculos&type=vehicle_detail
```
- Serve arquivo específico do storage
- Inclui headers de cache (1 hora)
- Retorna 404 se arquivo não existir

## 📋 **Vantagens da Nova Estrutura**

### ✅ **Persistência**
- Arquivos não são perdidos em deployments
- Sobrevive a reinicializações do servidor
- Backup automático com o sistema de arquivos

### ✅ **Performance**
- Cache de 1 hora nos headers HTTP
- Servir arquivos diretamente do storage
- Evita regeneração desnecessária

### ✅ **Organização**
- Um diretório por tenant
- Arquivos separados por tipo
- Estrutura clara e escalável

### ✅ **Segurança**
- **Geração**: Acesso exclusivo Super Admin (autenticação obrigatória)
- **Serviço**: Arquivos fora do diretório público
- **Validação**: Tenant validado antes de servir
- **Controle**: Acesso controlado via API

## 🔧 **Configuração de Produção**

### **Nginx/Apache**
Configure o servidor web para servir arquivos do storage:

```nginx
# Nginx
location /sitemaps/ {
    alias /path/to/storage/app/sitemaps/;
    expires 1h;
    add_header Cache-Control "public, max-age=3600";
}
```

### **CDN**
Configure CDN para cachear os sitemaps:
- Cache TTL: 1 hora
- Headers: `Cache-Control: public, max-age=3600`

## 📊 **Monitoramento**

### **Logs**
Todos os eventos são logados:
```php
Log::info('Sitemap salvo com sucesso', [
    'tenant' => $tenant->subdomain,
    'type' => $type,
    'filepath' => $filepath,
    'urls_count' => substr_count($xml, '<url>')
]);
```

### **Métricas**
- Número de URLs por sitemap
- Tamanho dos arquivos
- Frequência de geração
- Erros de geração/serviço

## 🚨 **Considerações Importantes**

1. **Permissões**: Garanta que o diretório `storage/app/sitemaps` tenha permissões de escrita
2. **Espaço**: Monitore o uso de disco conforme o número de tenants cresce
3. **Backup**: Inclua o diretório `storage/app/sitemaps` nos backups
4. **Limpeza**: Implemente limpeza de arquivos antigos se necessário

## 🔄 **Migração**

Se você já tinha sitemaps no diretório `public/sitemaps/`, execute:

```bash
# Mover arquivos existentes
mv public/sitemaps/* storage/app/sitemaps/

# Remover diretório antigo
rm -rf public/sitemaps

# Criar link simbólico
ln -sf ../storage/app/sitemaps public/sitemaps
```
