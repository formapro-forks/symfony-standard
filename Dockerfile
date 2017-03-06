FROM formapro/nginx-php-fpm:latest-all-exts

WORKDIR /app
ENV NGINX_WEB_ROOT=/app/web
ENV SYMFONY_ENV=dev
ENV SYMFONY_DEBUG=true