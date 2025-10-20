# Imagen base con PHP y Apache
FROM php:8.2-apache

# Copiar todo el proyecto al contenedor
COPY . /var/www/html/

# Instalar extensión mysqli
RUN docker-php-ext-install mysqli

# Cambiar el DocumentRoot y DirectoryIndex de Apache
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/php/Main|g' /etc/apache2/sites-available/000-default.conf \
    && echo "<Directory /var/www/html/php/Main>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Establecer el directorio de trabajo
WORKDIR /var/www/html/php/Main

# Permitir acceso a Assets desde cualquier ruta
RUN echo "<Directory /var/www/html/Assets>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" >> /etc/apache2/apache2.conf


# Exponer el puerto para Render
EXPOSE 8080
