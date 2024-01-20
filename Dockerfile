# Gunakan base image Ubuntu
FROM ubuntu:22.04

# Install dependensi yang diperlukan
RUN apt-get update && apt-get install -y \
    php-cli \
    php-mbstring \
    php-xml \
    composer

# Set environment variable
ENV COMPOSER_HOME=/composer

# Set working directory
WORKDIR /var/www/html

# Salin file composer.json dan composer.lock ke dalam container
COPY composer.json composer.lock ./

# Install dependensi PHP menggunakan Composer
RUN composer install --no-scripts --no-autoloader

# Salin seluruh proyek ke dalam container
COPY . .

# Generate autoload files
RUN composer dump-autoload --no-scripts --optimize

# Expose port 8000 (sesuaikan dengan port yang digunakan oleh Laravel)
EXPOSE 8000

# CMD yang akan dijalankan saat container dimulai
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
