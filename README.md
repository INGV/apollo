|**Service**|**Main**|**Develop**|
|---|---|---|
|CI/CD|[![pipeline status](https://gitlab.rm.ingv.it/caravel/apollo/badges/main/pipeline.svg)](https://gitlab.rm.ingv.it/caravel/apollo/-/commits/main)|[![pipeline status](https://gitlab.rm.ingv.it/caravel/apollo/badges/develop/pipeline.svg)](https://gitlab.rm.ingv.it/caravel/apollo/-/commits/develop)|

# apollo

## --- START - New ---
## Clone project
```
git clone --recursive https://gitlab.rm.ingv.it/caravel/apollo.git apollo
```
or using deploy token (*read-only*):
```
git clone --recursive https://gitlab+deploy-token-71:TWxRfoetzHXxpsLbckbb@gitlab.rm.ingv.it/caravel/apollo.git apollo
```
## Develop
In *develop* mode, all files are "binded" into the container; it is useful to develop code.
```
cd dante
# (optional; build image locally) docker compose -f docker-compose.yml -f docker-compose.dev.yml build --no-cache --pull --progress=plain
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --remove-orphans -d
```

## --- END - New ---


```
$ git clone --recursive https://gitlab+deploy-token-71:TWxRfoetzHXxpsLbckbb@gitlab.rm.ingv.it/caravel/apollo.git
$ cd apollo
$ git submodule update --init --recursive
```

## Configure Laradock
Set ports (nginx, etc...) into  `./apollo/configure_laradock.sh` and run:
```
$ ./apollo/configure_laradock.sh
```

## Configure Laravel - 1st step
Copy laravel environment file and set it:
```
$ cp ./.env.example ./.env
```

## Start apollo
First, build docker images:

```
cd laradock-apollo && \
docker-compose build --no-cache --pull nginx redis php-fpm workspace docker-in-docker && \
docker-compose up -d nginx redis php-fpm workspace docker-in-docker && \
cd ..
```

## Configure Laravel - 2nd step
### !!! On Linux machine and no 'root' user !!!
```
cd laradock-apollo && \
docker-compose exec -T --user=laradock workspace composer install && \
docker-compose exec -T --user=laradock workspace php artisan key:generate && \
docker-compose exec -T --user=laradock workspace chown -R $(id -u):$(id -g) ./storage && \
docker-compose exec -T --user=laradock workspace chown -R $(id -u):$(id -g) ./bootstrap/cache/ && \
cd ..
```

### !!! Others !!!
```
cd laradock-apollo && \
docker-compose exec -T workspace composer install && \
docker-compose exec -T workspace php artisan key:generate && \
docker-compose exec -T workspace chown -R $(id -u):$(id -g) ./storage && \
docker-compose exec -T workspace chown -R $(id -u):$(id -g) ./bootstrap/cache/ && \
cd ..
```

## Build hyp2000 image
build **hyp2000** docker image into *php-fpm* container:
```
cd laradock-apollo && \
docker-compose exec -T php-fpm sh -c "if docker image ls | grep -q hyp2000 ; then echo \" nothing to do\"; else cd hyp2000 && docker build --tag hyp2000:ewdevgit -f DockerfileEwDevGit .; fi" && \
cd ..
```

### Keep on mind!
The **hyp2000** docker image is built in the *php-fpm* container; if you destroy or rebuild *php-fpm* container, remember to re-build hyp2000 image.

## Build pyml image
build **pyml** docker image into *php-fpm* container:
```
cd laradock-apollo && \
docker-compose exec -T php-fpm sh -c "if docker image ls | grep -q pyml ; then echo \" nothing to do\"; else cd pyml && docker build --tag pyml .; fi" && \
cd ..
```

### Keep on mind!
The **pyml** docker image is built in the *php-fpm* container; if you destroy or rebuild *php-fpm* container, remember to re-build pyml image.

## How to use it
When all containers are started, connect to: 
- http://<your_host>:<your_port>/

default is:
- http://localhost:8780/

If all works, you should see a web page with OpenAPI3 specification to interact with WS.

## Swagger - OpenAPI
### Link to OpenAPI Spec: 
- https://gitlab.rm.ingv.it/caravel/apollo/-/blob/main/public/api/0.0.2/openapi.yaml
### Download Generated Class
- http://caravel.gitpages.rm.ingv.it/apollo/

## Thanks to
This project uses the [Laradock](https://github.com/laradock/laradock) idea to start docker containers

## Contribute
Please, feel free to contribute.

## Author
(c) 2022 Valentino Lauciani valentino.lauciani[at]ingv.it 

Istituto Nazionale di Geofisica e Vulcanologia, Italia
