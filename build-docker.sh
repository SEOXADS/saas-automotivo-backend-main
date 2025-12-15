#!/bin/bash

echo "🐳 Build otimizado para SaaS Automotivo Backend"
echo "==============================================="

# Configurações
IMAGE_NAME="saas-automotivo-backend"
TAG="latest"
BUILD_CONTEXT="."

echo ""
echo "🧹 Limpando builds anteriores..."
docker system prune -f

echo ""
echo "🔧 Verificando Dockerfile..."
if [ ! -f "Dockerfile" ]; then
    echo "❌ Dockerfile não encontrado!"
    exit 1
fi

echo ""
echo "📦 Iniciando build..."
echo "   Image: $IMAGE_NAME:$TAG"
echo "   Context: $BUILD_CONTEXT"

# Build com cache otimizado
docker build \
    --tag $IMAGE_NAME:$TAG \
    --build-arg BUILDKIT_INLINE_CACHE=1 \
    --cache-from $IMAGE_NAME:$TAG \
    --progress=plain \
    $BUILD_CONTEXT

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Build concluído com sucesso!"
    echo ""
    echo "📊 Informações da imagem:"
    docker images $IMAGE_NAME:$TAG

    echo ""
    echo "🚀 Para executar:"
    echo "   docker run -p 8000:80 $IMAGE_NAME:$TAG"
    echo ""
    echo "🔍 Para inspecionar:"
    echo "   docker inspect $IMAGE_NAME:$TAG"
    echo ""
    echo "🧪 Para testar:"
    echo "   curl http://localhost:8000/health"

else
    echo ""
    echo "❌ Build falhou!"
    echo ""
    echo "🔍 Verifique:"
    echo "   1. Se o Dockerfile está correto"
    echo "   2. Se todos os arquivos necessários existem"
    echo "   3. Se há erros de sintaxe"
    echo "   4. Se há problemas de permissão"
    exit 1
fi
