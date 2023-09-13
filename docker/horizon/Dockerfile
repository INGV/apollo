FROM webdevops/php:8.1 as base

ARG ENV_UID=1000
ARG ENV_GID=1000
ENV WEB_DOCUMENT_ROOT=/app/public
#ENV PHP_DISMOD=bz2,calendar,exiif,ffi,intl,gettext,ldap,mysqli,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsm,sysvshm,shmop,xsl,zip,gd,apcu,vips,yaml,imagick,mongodb,amqp

RUN apt-get update \
    && apt-get install -y \
    procps \
    iputils-ping \
    && rm -rf /var/lib/apt/lists/*

# Set default User and Group id from arguments
# If UID and/or GID are equal to zero then new user and/or group are created
RUN echo ENV_UID=${ENV_UID}
RUN echo ENV_GID=${ENV_GID}

# Check if GID already exists
RUN cat /etc/group
RUN \
    if grep -q -e "[^:][^:]*:[^:][^:]*:${ENV_GID}:.*$" /etc/group; then \
    GROUP_NAME_ALREADY_EXISTS=$(grep  -e "[^:][^:]*:[^:][^:]*:${ENV_GID}:.*$" /etc/group | cut -f 1 -d':'); \
    echo "GID ${ENV_GID} already exists with group name ${GROUP_NAME_ALREADY_EXISTS}"; \
    #groupmod -n ${GROUP_NAME} ${GROUP_NAME_ALREADY_EXISTS}; \
    else \
    groupmod -g ${ENV_GID} application; \
    cat /etc/group; \
    fi

# Check if UID already exists
RUN cat /etc/passwd
RUN \
    if grep -q -e "[^:][^:]*:[^:][^:]*:${ENV_UID}:[^:][^:]*:.*$" /etc/passwd; then \
    USER_NAME_ALREADY_EXISTS=$(grep  -e "[^:][^:]*:[^:][^:]*:${ENV_UID}:[^:][^:]*:.*$" /etc/passwd | cut -f 1 -d':'); \
    echo "UID ${ENV_UID} already exists with user name ${USER_NAME_ALREADY_EXISTS}"; \
    #usermod -d ${HOMEDIR_USER} -g ${ENV_GID} -l ${USER_NAME} ${USER_NAME_ALREADY_EXISTS}; \
    else \
    usermod -u ${ENV_UID} application; \
    cat /etc/passwd; \
    fi

WORKDIR /app
COPY ./docker/horizon/extra /opt/docker

##### Develop #####
FROM base as apollo-dev
#RUN chown -R application:application .
#USER application

##### Production #####
FROM base as apollo-prod
COPY --chown=${ENV_UID}:${ENV_GID} . .
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
