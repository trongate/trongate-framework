# Use the official NGINX Unit base image with PHP 8.3
FROM unit:php8.4

# Install required dependencies and PHP extensions
# Install required dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        zlib1g-dev \
        libpng-dev \
        libzip-dev \
        libicu-dev \
        gcc \
        g++ \
        make \
        autoconf \
        libc-dev \
    && docker-php-ext-install gd zip intl pdo pdo_mysql \
    # Install Xdebug
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    # Clean up build dependencies
    && apt-get purge -y --auto-remove \
        gcc \
        g++ \
        make \
        autoconf \
        libc-dev \
    && rm -rf /var/lib/apt/lists/*

# Set the working directory
WORKDIR /var/www/html

# Copy your application code into the container
COPY . /var/www/html

# Configure NGINX Unit to serve the PHP application
COPY ./docker/nginx/unit.config.json /docker-entrypoint.d/

# Expose the port that NGINX Unit will listen on
EXPOSE 80