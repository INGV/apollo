FROM webdevops/php:8.1 as base

ENV WEB_DOCUMENT_ROOT=/app/public
#ENV PHP_DISMOD=bz2,calendar,exiif,ffi,intl,gettext,ldap,mysqli,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsm,sysvshm,shmop,xsl,zip,gd,apcu,vips,yaml,imagick,mongodb,amqp

RUN apt-get update \
    && apt-get install -y \
    procps \
    iputils-ping \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY ./docker/horizon/extra /opt/docker


FROM base as apollo-dev
#RUN chown -R application:application .
#USER application

FROM base as apollo-prod
COPY --chown=1000:1000 . .
COPY .env.example .env
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN php artisan key:generate
#RUN rm -v database/migrations/*_create_failed_jobs_table.php
#RUN php artisan queue:failed-table
#RUN php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag="sanctum-migrations"
#RUN php artisan migrate:fresh --database=pgsql_dante
#RUN php artisan db:seed --database=pgsql_dante -vvv
#RUN chown -R $(id -u):$(id -g) ./storage
#RUN chown -R $(id -u):$(id -g) ./bootstrap/cache/
#RUN php artisan vendor:publish --tag=horizon-assets --force
RUN chown -R application:application .
#USER application
