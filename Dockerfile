# Dockerfile para ChatCenter - EasyPanel
# Optimizado para producción con variables de entorno

FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        gd \
        zip \
        mysqli \
        mbstring \
        opcache \
        exif \
    && a2enmod rewrite

# Configurar variables de entorno de Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/cms
ENV APACHE_SERVER_ADMIN webmaster@localhost

# Copiar código fuente
COPY chatcenter/ /var/www/html/

# Crear directorio de logs
RUN mkdir -p /var/www/html/logs

# Instalar Composer para dependencias PHP
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar dependencias de Composer
RUN cd /var/www/html/api && composer install --no-dev --optimize-autoloader
RUN cd /var/www/html/cms/extensions && composer install --no-dev --optimize-autoloader

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 777 /var/www/html/cms/uploads
RUN chmod -R 777 /var/www/html/cms/views/modules
RUN chmod -R 777 /var/www/html/cms/views/pages
RUN chmod -R 777 /var/www/html/cms/views/forms
RUN chmod -R 777 /var/www/html/cms/views/head
RUN chmod -R 777 /var/www/html/logs

# Copiar configuración de Apache personalizada
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Script de inicialización automática
COPY init-script.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/init-script.sh

# Configurar entrada
ENTRYPOINT ["/usr/local/bin/init-script.sh"]
CMD ["apache2-foreground"]

# Exponer puerto 8080 para EasyPanel
EXPOSE 8080
EXPOSE 80