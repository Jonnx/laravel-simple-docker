FROM php:7.3-apache

# INSTALL DEPENDENCIES
RUN \
    # Install: APT / PHP Docker Dependencies
    apt-get update && apt-get install -y \
        apt-transport-https \ 
        apt-utils \
        wget \
        gnupg \  
        git \ 
        supervisor \ 
        libmcrypt-dev \
        zip \ 
        unzip \ 
        curl \
        git \
        libpq5 \ 
        libpq-dev \
        libxml2-dev \
        vim \
        ntp \ 
        libpng-dev \ 
        libjpeg-dev \
        && \
    docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install \ 
        pdo_mysql \
        && \
    # PHP-Redis
    pecl install -o -f redis && \
    rm -rf /tmp/pear && \
    docker-php-ext-enable redis && \
    # Install: COMPOSER
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"
    

COPY . /var/www/html/

# CONFIGURE
RUN \
    # apache
    cp ./docker/virtualhost.conf /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite && \
    # supervisor
    service supervisor stop && \
    cp ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf && \
    # cleanup
    chown -R www-data . && \
    composer dump-autoload

ENTRYPOINT [ "/var/www/html/docker/entrypoint.sh" ]