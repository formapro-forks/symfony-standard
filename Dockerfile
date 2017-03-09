FROM ubuntu:16.04

MAINTAINER Maksim Kotliar <kotlyar.maksim@gmail.com>

RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests php ca-certificates supervisor && \
    rm -rf /var/lib/apt/lists/*

RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests php-dev php-pear make && \
    pecl install swoole && \
    echo "extension=swoole.so" > /etc/php/7.0/cli/conf.d/10-swoole.ini && \
    apt-get remove -y php-dev php-pear make && \
    rm -rf /var/lib/apt/lists/*

## exts
RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests \
    php-mongodb php-curl php-intl php-soap php-xml php-mcrypt php-bcmath \
    php-mysql php-amqp php-mbstring php-ldap php-zip && \
    rm -rf /var/lib/apt/lists/*

COPY docker/supervisor.conf /etc/supervisor/conf.d/boom_app.conf
COPY docker/entrypoint.sh /entrypoint.sh

CMD /entrypoint.sh

WORKDIR /app

EXPOSE 80