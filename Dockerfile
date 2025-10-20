FROM php:8.2-apache

# Copiar todo el proyecto
COPY . /var/www/html/

# Instalar extensión mysqli
RUN docker-php-ext-install mysqli

# Definir DocumentRoot en php/Main
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/php/Main|g' /etc/apache2/sites-available/000-default.conf \
    && echo "<Directory /var/www/html/php/Main>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Permitir acceso a Assets para CSS e imágenes
RUN echo "<Directory /var/www/html/Assets>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n\
Alias /Assets /var/www/html/Assets" >> /etc/apache2/apache2.conf

# Exponer puerto
EXPOSE 8080

WORKDIR /var/www/html/php/Main
