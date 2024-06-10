# BASE IMAGE
FROM php:8.1-apache
# LABEL
LABEL maintainer="praktikan"
# INSTALL
RUN apt-get update -y \
&& apt-get install -y --no-install-recommends \
wget \
git \
zip \
unzip \
libxml2-dev \
&& docker-php-ext-install pdo \
mysqli \
pdo_mysql \
soap \
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/*
# COPY source code
COPY . /var/www/html/api_intero
# Expose Port
EXPOSE 8080
# ENTRYPOINT SETUP
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
