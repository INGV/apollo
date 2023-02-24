FROM webdevops/php-nginx:8.1 as base

ENV WEB_DOCUMENT_ROOT=/app/public
#ENV PHP_DISMOD=bz2,calendar,exiif,ffi,intl,gettext,ldap,mysqli,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsm,sysvshm,shmop,xsl,zip,gd,apcu,vips,yaml,imagick,mongodb,amqp
#USER application
WORKDIR /app

#RUN if [ ! -d /opt/docker/etc/cron ]; then mkdir -p /opt/docker/etc/cron; fi
#COPY ./docker/apollo/extra/etc/cron/application /opt/docker/etc/cron/
COPY ./docker/apollo/extra/apolloFixPermissions.sh /entrypoint.d/
COPY ./docker/apollo/extra/apolloBuildDindImages.sh /entrypoint.d/

# Install docker client
RUN curl -sS https://download.docker.com/linux/static/stable/x86_64/docker-20.10.3.tgz -o /tmp/docker.tar.gz \
    && tar -xzf /tmp/docker.tar.gz -C /tmp/ \
    && cp /tmp/docker/docker* /usr/local/bin \
    && chmod +x /usr/local/bin/docker*

FROM base as apollo-dev
#RUN chown -R application:application .
#USER application

FROM base as apollo-prod
COPY . .
#COPY --chown 1000:1000 src /app                    # https://github.com/webdevops/Dockerfile/issues/366
COPY .env.example .env
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN php artisan key:generate
RUN chown -R application:application .
#USER application
