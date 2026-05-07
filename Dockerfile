FROM php:8.2-apache

# Instalar dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    libonig-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd mbstring

# CORREÇÃO CRÍTICA: Desativar MPMs conflitantes (Erro AH00534) e configurar porta dinâmica do Railway
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork \
    && sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar os arquivos do projeto para o container
COPY . /var/www/html/

# Configurar permissões seguras
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]