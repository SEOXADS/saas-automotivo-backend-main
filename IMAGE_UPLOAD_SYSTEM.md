# Sistema de Upload de Imagens para Veículos

## Visão Geral

Este sistema permite o upload, gerenciamento e exibição de imagens para veículos no sistema SaaS automotivo. As imagens são organizadas por tenant e veículo, com suporte a múltiplas imagens por veículo.

## Estrutura de Armazenamento

```
storage/app/public/
├── tenants/
│   ├── 1/                    # ID do tenant
│   │   └── vehicles/
│   │       ├── 5/            # ID do veículo
│   │       │   ├── image1.jpg
│   │       │   ├── image2.jpg
│   │       │   └── image3.jpg
│   │       └── 6/
│   │           └── image1.jpg
│   └── 2/
│       └── vehicles/
│           └── 1/
│               └── image1.jpg
```

## Endpoints da API

### 1. Upload de Imagens

**POST** `/api/vehicles/{id}/images`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (Form Data):**
```
images[]: [arquivo1.jpg]
images[]: [arquivo2.jpg]
images[]: [arquivo3.jpg]
```

**Resposta de Sucesso:**
```json
{
    "success": true,
    "message": "Imagens enviadas com sucesso",
    "data": [
        {
            "id": 1,
            "filename": "1234567890_abc123.jpg",
            "path": "tenants/1/vehicles/5/1234567890_abc123.jpg",
            "url": "http://localhost:8000/storage/tenants/1/vehicles/5/1234567890_abc123.jpg",
            "is_primary": true,
            "size": 1024000,
            "mime_type": "image/jpeg"
        }
    ]
}
```

### 2. Listar Imagens de um Veículo

**GET** `/api/vehicles/{id}/images`

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "filename": "1234567890_abc123.jpg",
            "path": "tenants/1/vehicles/5/1234567890_abc123.jpg",
            "url": "http://localhost:8000/storage/tenants/1/vehicles/5/1234567890_abc123.jpg",
            "is_primary": true,
            "size": 1024000,
            "mime_type": "image/jpeg",
            "width": 1920,
            "height": 1080,
            "sort_order": 1
        }
    ],
    "message": "Imagens do veículo listadas com sucesso"
}
```

### 3. Deletar Imagem

**DELETE** `/api/vehicles/{id}/images/{imageId}`

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta:**
```json
{
    "success": true,
    "message": "Imagem deletada com sucesso"
}
```

### 4. Definir Imagem como Primária

**POST** `/api/vehicles/{id}/images/{imageId}/primary`

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta:**
```json
{
    "success": true,
    "message": "Imagem definida como primária com sucesso"
}
```

### 5. Reordenar Imagens

**POST** `/api/vehicles/{id}/images/reorder`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
    "image_order": [3, 1, 2, 4]
}
```

**Resposta:**
```json
{
    "success": true,
    "message": "Imagens reordenadas com sucesso"
}
```

## Endpoints Públicos (Sem Autenticação)

### 1. Servir Imagem

**GET** `/api/public/images/{tenantId}/{vehicleId}/{filename}`

**Exemplo:**
```
GET /api/public/images/1/5/1234567890_abc123.jpg
```

**Resposta:** A imagem é retornada diretamente com headers apropriados para cache.

### 2. Obter URL da Imagem

**GET** `/api/public/images/{tenantId}/{vehicleId}/{filename}/url`

**Resposta:**
```json
{
    "url": "http://localhost:8000/api/public/images/1/5/1234567890_abc123.jpg"
}
```

## Validações

### Tipos de Arquivo Permitidos
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

### Limites
- **Tamanho máximo:** 5MB por imagem
- **Quantidade máxima:** 10 imagens por veículo
- **Formato:** Apenas arquivos de imagem válidos

## Características do Sistema

### 1. Organização por Tenant
- Cada tenant tem sua própria pasta de imagens
- Isolamento completo entre tenants
- URLs únicas por tenant

### 2. Imagem Primária
- A primeira imagem enviada é automaticamente definida como primária
- Apenas uma imagem pode ser primária por veículo
- Imagens primárias não podem ser deletadas

### 3. Ordenação
- Suporte a ordenação personalizada das imagens
- Campo `sort_order` para controle de exibição

### 4. Cache e Performance
- Headers de cache configurados para 1 ano
- URLs públicas otimizadas para CDN
- Suporte a diferentes tipos de imagem

### 5. Segurança
- Validação de tenant e veículo
- Verificação de propriedade das imagens
- Sanitização de nomes de arquivo

## Exemplo de Uso no Frontend

### Upload de Imagens
```javascript
const formData = new FormData();
formData.append('images[]', file1);
formData.append('images[]', file2);

fetch('/api/vehicles/5/images', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`
    },
    body: formData
})
.then(response => response.json())
.then(data => {
    console.log('Imagens enviadas:', data.data);
});
```

### Exibir Imagens
```html
<!-- Imagem primária -->
<img src="/api/public/images/1/5/1234567890_abc123.jpg" alt="Veículo">

<!-- Galeria de imagens -->
<div class="gallery">
    <img src="/api/public/images/1/5/image1.jpg" alt="Imagem 1">
    <img src="/api/public/images/1/5/image2.jpg" alt="Imagem 2">
    <img src="/api/public/images/1/5/image3.jpg" alt="Imagem 3">
</div>
```

## Configuração do Storage

### 1. Link Simbólico
```bash
php artisan storage:link
```

### 2. Permissões
```bash
chmod -R 775 storage/app/public
chown -R www-data:www-data storage/app/public
```

### 3. Configuração do .env
```env
FILESYSTEM_DISK=public
```

## Tratamento de Erros

### Erros Comuns

1. **Arquivo muito grande**
   ```json
   {
       "error": "Dados inválidos",
       "messages": {
           "images.0": ["A imagem não pode ter mais que 5MB."]
       }
   }
   ```

2. **Tipo de arquivo inválido**
   ```json
   {
       "error": "Dados inválidos",
       "messages": {
           "images.0": ["A imagem deve ser do tipo: jpeg, png, jpg, gif, webp."]
       }
   }
   ```

3. **Veículo não encontrado**
   ```json
   {
       "error": "Veículo não encontrado"
   }
   ```

4. **Imagem não encontrada**
   ```json
   {
       "error": "Imagem não encontrada"
   }
   ```

## Monitoramento e Manutenção

### Logs
- Todas as operações são logadas
- Erros são capturados e reportados
- Auditoria completa de uploads e exclusões

### Limpeza
- Imagens deletadas são removidas fisicamente
- Verificação de integridade entre banco e arquivos
- Suporte a backup e restauração

## Considerações de Performance

1. **CDN**: URLs públicas são otimizadas para CDN
2. **Cache**: Headers de cache configurados para 1 ano
3. **Compressão**: Suporte a diferentes formatos de imagem
4. **Lazy Loading**: Suporte a carregamento sob demanda

## Segurança

1. **Validação de Tenant**: Cada tenant só acessa suas próprias imagens
2. **Sanitização**: Nomes de arquivo são sanitizados
3. **Tipos MIME**: Validação rigorosa de tipos de arquivo
4. **Tamanho**: Limite de tamanho para prevenir ataques
5. **Autenticação**: Todas as operações de modificação requerem autenticação

## Troubleshooting

### Problemas Comuns

1. **Imagem não aparece**
   - Verificar se o link simbólico foi criado
   - Verificar permissões da pasta storage
   - Verificar se o arquivo existe fisicamente

2. **Erro de upload**
   - Verificar tamanho do arquivo
   - Verificar tipo de arquivo
   - Verificar permissões de escrita

3. **URLs quebradas**
   - Verificar configuração do .env
   - Verificar se o servidor está rodando
   - Verificar se as rotas estão registradas

### Comandos Úteis

```bash
# Recriar link simbólico
php artisan storage:link

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Verificar rotas
php artisan route:list | grep images

# Verificar permissões
ls -la storage/app/public
```
