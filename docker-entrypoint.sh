#!/bin/sh
set -e

echo "============================================"
echo "  SportFlow API - Inicializando..."
echo "============================================"

# Gera a APP_KEY se não existir
if [ -z "$APP_KEY" ]; then
    echo ">> Gerando APP_KEY..."
    php artisan key:generate --force
fi

# Limpa e recria os caches de configuração
echo ">> Otimizando cache de configuração..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Executa as migrations (se o banco estiver acessível)
echo ">> Executando migrations..."
php artisan migrate --force || echo ">> AVISO: Migrations falharam (banco indisponível?)"

# Cria o link simbólico do storage
echo ">> Criando link do storage..."
php artisan storage:link || true

echo "============================================"
echo "  SportFlow API - Pronto!"
echo "============================================"

# Executa o CMD passado (php-fpm por padrão)
exec "$@"
