FROM php:8.2-apache

# Install dependencies for PHP extensions and SSL/TLS support
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    cron \
    git \
    unzip \
    ca-certificates \
    openssl \
    && update-ca-certificates

# Install MongoDB Extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install RabbitMQ (AMQP) Extension Dependencies and Extension
RUN apt-get install -y librabbitmq-dev \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# Retrieve Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache Rewrites
RUN a2enmod rewrite

# Set Working Directory
WORKDIR /var/www/html

# Copy Cron Script
COPY docker/daily_script.sh /usr/local/bin/daily_script.sh
RUN chmod +x /usr/local/bin/daily_script.sh

# Copy Entrypoint Script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh


# Setup Cron
# Create a crontab entry to run the script daily at midnight
RUN echo "0 0 * * * root /usr/local/bin/daily_script.sh" >> /etc/cron.d/app-cron
RUN chmod 0644 /etc/cron.d/app-cron
RUN crontab /etc/cron.d/app-cron

# Start Application via Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
