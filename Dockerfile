FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    curl \
    apt-transport-https \
    gnupg2 \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    nginx \
    git \
    libmcrypt-dev \
    libzip-dev \
    zip \
    librabbitmq-dev \
    supervisor \
    unixodbc-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y DEBIAN_FRONTEND=noninteractive apt-get install -y msodbcsql17 unixodbc unixodbc-dev \
    || apt-get -o Dpkg::Options::="--force-overwrite" install -f -y \
    && apt-get clean

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql zip sockets mbstring exif pcntl bcmath opcache

RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && echo "extension=sqlsrv.so" > /usr/local/etc/php/conf.d/sqlsrv.ini \
    && echo "extension=pdo_sqlsrv.so" > /usr/local/etc/php/conf.d/pdo_sqlsrv.ini


RUN mkdir -p /var/log/supervisor
COPY ./supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf


WORKDIR /var/www/html


COPY . .


RUN composer install --no-interaction --no-scripts --no-autoloader \
    && composer dump-autoload --optimize


RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache


RUN apt-get clean && rm -rf /var/lib/apt/lists/*

EXPOSE 9000
EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
