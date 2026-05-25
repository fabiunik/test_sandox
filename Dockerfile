FROM php:8.2-apache

# Instalar extensões necessárias do PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd mbstring

# Garantir que os módulos conflitantes sejam desativados no build
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork

# Habilitar mod_rewrite do Apache (útil para URLs amigáveis futuramente)
RUN a2enmod rewrite

# Copiar os arquivos do projeto para o container
COPY . /var/www/html/

# Configurar permissões e criar pasta de uploads
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
    
#instalar o Composer
RUN composer install --no-dev --optimize-autoloader
    
# Define a porta 80 como padrão caso o Railway não envie a variável $PORT
ENV PORT=80

EXPOSE ${PORT}

# Configura a porta dinâmica e limpa MPMs no momento da execução
CMD sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf && \
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf && \
    a2dismod mpm_event mpm_worker || true && a2enmod mpm_prefork && \
    apache2-foreground