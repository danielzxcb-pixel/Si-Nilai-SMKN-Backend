FROM php:8.2-apache

# Install dependensi dan ekstensi yang dibutuhkan CI4
RUN apt-get update && apt-get install -y \
    libicu-dev \
    git \
    unzip \
    && docker-php-ext-install intl pdo pdo_mysql mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite
RUN a2enmod rewrite

# Buat konfigurasi vhost khusus mengarah ke folder public CI4
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    </VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copy project
COPY . /var/www/html/

# Buat folder writable & subfoldernya lalu atur permission
RUN mkdir -p /var/www/html/writable/cache \
    /var/www/html/writable/logs \
    /var/www/html/writable/session \
    /var/www/html/writable/uploads \
    /var/www/html/public && \
    chown -R www-data:www-data /var/www/html/writable /var/www/html/public && \
    chmod -R 775 /var/www/html/writable

EXPOSE 80
CMD ["apache2-foreground"]