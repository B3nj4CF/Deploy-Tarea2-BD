# Imagen base de PHP con Apache
FROM php:8.2-apache

# Instala extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilita módulos de Apache
RUN a2enmod rewrite

# Copia tu aplicación al contenedor
COPY . /var/www/html/

# Configura permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Establece el directorio de trabajo
WORKDIR /var/www/html/php/Main

# Asegura que los recursos estáticos (CSS, imágenes) sean accesibles
RUN echo "<Directory /var/www/html/Assets>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" >> /etc/apache2/apache2.conf

# Expone el puerto estándar de Apache
EXPOSE 80

# Inicia Apache en primer plano
CMD ["apache2-foreground"]
