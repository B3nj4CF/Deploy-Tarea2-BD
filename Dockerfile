# Imagen base PHP + Apache
FROM php:8.2-apache

# Instalar extensiones requeridas
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar módulos de Apache
RUN a2enmod rewrite

# Copiar el contenido de tu proyecto
COPY . /var/www/html/

# Establecer permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 🔥 Configurar DocumentRoot y acceso a Assets
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/php/Main|g' /etc/apache2/sites-available/000-default.conf \
    && echo "<Directory /var/www/html/php/Main>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    <Directory /var/www/html/Assets>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Exponer puerto 80
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
