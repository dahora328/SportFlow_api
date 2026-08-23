# =============================================================================
# SportFlow API - Dockerfile
# Laravel 12 | PHP 8.2-Apache | PostgreSQL
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
    --prefer-dist \
    --ignore-platform-reqs

# Copia o restante do código
COPY . .

# Gera o autoloader otimizado com o código completo
RUN composer dump-autoload --optimize --no-dev

# ---------------------------------------------------------------------------
# Stage 2: Imagem final - PHP 8.2-Apache
# ---------------------------------------------------------------------------
FROM php:8.2-apache

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
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Ativa o mod_rewrite do Apache (essencial para as rotas do Laravel)
RUN a2enmod rewrite

# Configura o diretório raiz do Apache para a pasta /public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

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

# Ajusta permissões para o usuário do Apache (www-data)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Copia o script de entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expõe a porta 80 (padrão do tráfego web HTTP)
EXPOSE 80

# Entrypoint: executa tarefas de inicialização (migrations, cache, etc)
ENTRYPOINT ["docker-entrypoint.sh"]

# Comando padrão: inicia o Apache em primeiro plano
CMD ["apache2-foreground"]  