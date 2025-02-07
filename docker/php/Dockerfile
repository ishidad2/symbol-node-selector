FROM php:8.3-fpm

EXPOSE 9000

LABEL maintainer="ishida_d"

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    vim \
    wget \
    libgmp-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    libonig-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gmp && \
    docker-php-ext-install gmp pdo_mysql mbstring bcmath

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www

# Copy php.ini (if needed)
COPY php.ini /usr/local/etc/php/

CMD ["php-fpm"]