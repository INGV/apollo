FROM webdevops/php-nginx:8.1 as base

ENV WEB_DOCUMENT_ROOT=/app/public
#ENV PHP_DISMOD=bz2,calendar,exiif,ffi,intl,gettext,ldap,mysqli,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsm,sysvshm,shmop,xsl,zip,gd,apcu,vips,yaml,imagick,mongodb,amqp

RUN apt-get update \
    && apt-get install -y \
    procps \
    iputils-ping \
    && rm -rf /var/lib/apt/lists/*

#USER application
WORKDIR /app

RUN if [ ! -d /opt/docker/etc/cron ]; then mkdir -p /opt/docker/etc/cron; fi
COPY ./docker/apollo/extra/etc/cron/application /opt/docker/etc/cron/
COPY ./docker/apollo/extra/etc/supervisor.d/laravel-horizon.conf /opt/docker/etc/supervisor.d/
COPY ./docker/apollo/extra/apollo01FixPermissions.sh /opt/docker/provision/entrypoint.d/
COPY ./docker/apollo/extra/apollo02PhpArtisanMigrate.sh /opt/docker/provision/entrypoint.d/


# Set bashrc
RUN echo "alias ll='ls -l'" >> ~/.bashrc

FROM base as apollo-dev
#RUN chown -R application:application .
#USER application

FROM base as apollo-prod
COPY --chown=1000:1000 . .
COPY .env.example .env
RUN rm -fr ./storage/app/data/hyp2000/*
RUN rm -fr ./storage/app/data/pyml/*
RUN rm -fr ./storage/logs/*
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN php artisan key:generate
#RUN chown -R application:application .
#USER application
