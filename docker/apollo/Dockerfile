FROM webdevops/php-nginx:8.1 as base

ENV WEB_DOCUMENT_ROOT=/app/public
#ENV PHP_DISMOD=bz2,calendar,exiif,ffi,intl,gettext,ldap,mysqli,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsm,sysvshm,shmop,xsl,zip,gd,apcu,vips,yaml,imagick,mongodb,amqp
#USER application
WORKDIR /app

#RUN if [ ! -d /opt/docker/etc/cron ]; then mkdir -p /opt/docker/etc/cron; fi
#COPY ./docker/apollo/extra/etc/cron/application /opt/docker/etc/cron/
COPY ./docker/apollo/extra/apollo01FixPermissions.sh /opt/docker/provision/entrypoint.d/
#COPY ./docker/apollo/extra/apollo02BuildDindImages.sh /opt/docker/provision/entrypoint.d/

COPY ./docker/apollo/extra/casa1.sh /opt/docker/provision/bootstrap.d/
COPY ./docker/apollo/extra/casa2.sh /opt/docker/provision/build.d/
COPY ./docker/apollo/extra/casa3.sh /opt/docker/provision/onbuild.d/

# Install docker client
RUN curl -sS https://download.docker.com/linux/static/stable/x86_64/docker-20.10.3.tgz -o /tmp/docker.tar.gz \
    && tar -xzf /tmp/docker.tar.gz -C /tmp/ \
    && cp /tmp/docker/docker* /usr/local/bin \
    && chmod +x /usr/local/bin/docker*

# Install ping
RUN apt-get update -y \
    && apt-get install -y iputils-ping

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
