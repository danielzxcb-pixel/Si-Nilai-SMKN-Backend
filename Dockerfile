FROM php:8.2-apache

# 1. Install ekstensi yang dibutuhkan aplikasi
RUN apt-get update && apt-get install -y \
    libicu-dev \
    git \
    unzip \
    && docker-php-ext-install intl pdo pdo_mysql mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Atasi bentrok MPM: Matikan worker/event, paksa hanya MPM prefork, aktifkan mod_rewrite & mod_headers
RUN a2dismod mpm_event mpm_worker || true && a2enmod mpm_prefork rewrite headers

# 3. Ubah DocumentRoot Apache ke folder public via environment variable & konfigurasi AllowOverride All
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Tambahkan konfigurasi routing Directory Apache (AllowOverride All + FallbackResource)
RUN echo '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>' > /etc/apache2/conf-available/sinilai-routing.conf && \
    a2enconf sinilai-routing

# 5. Salin Composer official multi-stage binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Salin source code aplikasi ke server
WORKDIR /var/www/html
COPY . /var/www/html/

# 7. Jalankan Composer Install untuk memastikan vendor/ dan firebase/php-jwt tersedia di production
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# 8. Buat struktur folder writable dan public, atur permission & script entrypoint
RUN mkdir -p /var/www/html/writable/cache \
    /var/www/html/writable/logs \
    /var/www/html/writable/session \
    /var/www/html/writable/uploads \
    /var/www/html/public && \
    chown -R www-data:www-data /var/www/html/writable /var/www/html/public && \
    chmod -R 775 /var/www/html/writable && \
    chmod +x /var/www/html/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/var/www/html/entrypoint.sh"]