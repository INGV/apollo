<p align="center"><a href="https://github.com/ingv/apollo" target="_blank"><img src="https://raw.githubusercontent.com/INGV/apollo/main/art/apollo.png" width="150" alt="Apollo Logo"></a></p>

## Apollo
Apollo is a Web Service developed using Laravel, specifically designed to expose seismic localization software "*hyp2000*" (https://github.com/INGV/hyp2000)   and magnitude calculation software "*PyML*" (https://github.com/INGV/pyml) through APIs. 

This work highlights the key features of Apollo, including its OpenAPI-based development, JSON communication, containerization, and open- source nature.

### Clone project
```
git clone --recursive https://gitlab.rm.ingv.it/caravel/apollo.git apollo
```
or using deploy token (*read-only*):
```
git clone --recursive https://gitlab+deploy-token-71:TWxRfoetzHXxpsLbckbb@gitlab.rm.ingv.it/caravel/apollo.git apollo
```
### Develop
In *develop* mode, all files are "binded" into the container; it is useful to develop code.
#### Configure Laravel
Copy laravel environment file and set it:
```
$ cp ./.env.example ./.env
```
#### Start containers

First of all, check you `UID` and `GID` with command: `id -u` and `id -g`.
If you have `UID=1000` and `UID=1000` run:
```
cd apollo
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --remove-orphans -d
```

otherwise, build locally docker images adn run:
```
cd apollo
docker compose --progress=plain -f docker-compose.yml -f docker-compose.dev.yml build --build-arg ENV_UID=$( id -u ) --build-arg ENV_GID=$( id -g ) --no-cache --pull
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --remove-orphans -d
```

install dependencies:
```
echo "----- 1 -----" && \
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T --user=application apollo composer install && \
echo "----- 2 -----" && \
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T --user=application apollo php artisan key:generate && \
echo "----- 3 -----" && \
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T --user=application apollo chown -R $(id -u):$(id -g) ./storage && \
echo "----- 4 -----" && \
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T --user=application apollo chown -R $(id -u):$(id -g) ./bootstrap/cache/ && \ 
```

### Production
In *production* mode, all files are "copied" into the container (also `.env`) and you do not need "bind" files:
```
cd apollo
docker compose -f docker-compose.yml -f docker-compose.prod.yml build --build-arg ENV_UID=$( id -u ) --build-arg ENV_GID=$( id -g ) --no-cache --pull --progress=plain
docker compose -f docker-compose.yml -f docker-compose.prod.yml up --remove-orphans -d
```
you can decide to:
-  *bind*/*mount* the `.env` file and/or the `storage/` directory. 

In this case, update `docker-compose.prod.yml` file.

### Contribute
Thanks to your contributions!

Here is a list of users who already contributed to this repository: \
<a href="https://github.com/ingv/apollo/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=ingv/apollo" />
</a>

### Author
(c) 2023 Valentino Lauciani valentino.lauciani[at]ingv.it 

Istituto Nazionale di Geofisica e Vulcanologia, Italia
