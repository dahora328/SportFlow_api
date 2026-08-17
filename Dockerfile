# =============================================================================
# SportFlow API - Dockerfile
# Laravel 12 | PHP 8.2-FPM | PostgreSQL
# =============================================================================

# ---------------------------------------------------------------------------
# Stage 1: Composer - instala dependências PHP
# ---------------------------------------------------------------------------
FROM composer:2 AS composer-stage

WORKDIR /app

# Copia apenas os arquivos de dependência primeiro (melhor cache de camadas)
COPY composer.json composer.lock ./

# Instala dependências de produção (sem dev)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# Copia o restante do código
COPY . .

# Gera o autoloader otimizado com o código completo
RUN composer dump-autoload --optimize --no-dev

# ---------------------------------------------------------------------------
# Stage 2: Imagem final - PHP 8.2-FPM
# ---------------------------------------------------------------------------
FROM php:8.2-fpm

LABEL maintainer="SportFlow Team"
LABEL description="SportFlow API - Laravel 12 Backend"

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP necessárias para o projeto
# pdo_pgsql  -> conexão com PostgreSQL
# mbstring   -> manipulação de strings multibyte
# exif       -> leitura de metadados de imagens
# pcntl      -> controle de processos (queues/horizon)
# bcmath     -> operações matemáticas de precisão
# gd         -> manipulação de imagens
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Define o diretório de trabalho
WORKDIR /var/www

# Copia o código da aplicação e as dependências do stage anterior
COPY --from=composer-stage /app /var/www

# Cria os diretórios de storage e cache necessários ao Laravel
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Ajusta permissões para o usuário do PHP-FPM (www-data)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Copia o script de entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expõe a porta padrão do PHP-FPM
EXPOSE 9000

# Entrypoint: executa tarefas de inicialização antes de subir o PHP-FPM
ENTRYPOINT ["docker-entrypoint.sh"]

# Comando padrão: inicia o PHP-FPM
CMD ["php-fpm"]
