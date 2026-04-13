FROM php:8.2-fpm

WORKDIR /var/www

# Устанавливаем нужные пакеты
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    curl \
    git \
    nano

# 👉 Устанавливаем нужные PHP-расширения
# Был pdo_mysql — удалён
RUN docker-php-ext-install pdo pdo_pgsql mbstring zip exif pcntl

# Копируем проект
COPY . /var/www

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Установка Node.js (20)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Устанавливаем зависимости Laravel
RUN composer install

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
