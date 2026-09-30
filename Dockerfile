FROM php:8.2-fpm

# Instal dependensi sistem dan ekstensi PHP yang diperlukan Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd

# Instal Composer (Package Manager PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Tentukan direktori kerja di dalam kontainer
WORKDIR /var/www

# Salin seluruh kode aplikasi ke kontainer
COPY . .

# Jalankan instalasi dependensi jika ada composer.json
RUN composer install --no-interaction --optimize-autoloader --no-dev || true

# Atur hak akses folder storage dan bootstrap (jika ada nanti saat deployment)
RUN chown -R www-data:www-data /var/www

RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/uploads.ini && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 9000
CMD ["php-fpm"]
