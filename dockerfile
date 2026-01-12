# Use an official PHP image as a base
#FROM php:8.1-apache
FROM php:8.1-apache-bullseye

# Install required PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    libicu-dev \
    libldap2-dev \
    libc-client2007e-dev \
    libkrb5-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        opcache \
        intl \
        ldap \
        imap \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy migration entrypoint script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Set permissions and create session folder + .user.ini
# Set permissions for app folders (tmp handled by host)
RUN mkdir -p \
    /var/www/html/upload \
    /var/www/html/application/runtime \
    /var/www/html/sessions \
    /var/www/html/tmp \
    /var/www/html/tmp/runtime \
    /var/www/html/tmp/assets \
    /var/www/html/tmp/runtime/twig_cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 \
        /var/www/html/upload \
        /var/www/html/application/runtime \
        /var/www/html/sessions \
        /var/www/html/tmp \
    \
    # Create built-in .user.ini for session settings
    && echo 'session.save_path="/var/www/html/sessions"' > /var/www/html/.user.ini \
    && echo 'session.gc_probability=1' >> /var/www/html/.user.ini \
    && echo 'session.gc_divisor=100' >> /var/www/html/.user.ini \
    && echo 'session.gc_maxlifetime=1440' >> /var/www/html/.user.ini \
    && chown www-data:www-data /var/www/html/.user.ini



RUN chown -R www-data:www-data /var/www

# Expose the web server port
EXPOSE 80

# Set the default command
CMD ["apache2-foreground"]
