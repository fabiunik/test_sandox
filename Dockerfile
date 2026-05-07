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

# Habilitar mod_rewrite do Apache (útil para URLs amigáveis futuramente)
RUN a2enmod rewrite

# Copiar os arquivos do projeto para o container
COPY . /var/www/html/

# Dar permissões para a pasta de uploads
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Expor a porta 80
EXPOSE 80

CMD ["apache2-foreground"]