# Development

## Setup development environment

In order to be able to develop Bibliograph with the same tools regardless of the development environment, 
a dockerized setup is used. You need the following prerequisites: 

 - Docker - See installation instructions on https://www.docker.com
   On the Mac, make sure to use Docker Desktop >= v2.3.3.0, otherwise
   the performance of MariaDB/MySql is abysmal.
 - NodeJS, latest LTS - It is suggested to use [nvm](https://github.com/nvm-sh/nvm).
 
First, run `npm install` to install all needed NPM modules.

Then, run `npm run env:install` to set up the docker containers with the
development environment. This will provide you with the following dockerized
services running in separate containers: 

 - PHPFarm with php versions 7.0 - 7.4 via Apache on localhost:8070-8074 
 - MariaDB on localhost:3036
 
Afterwards, run `npm run env:servers:start` .

# To do
 - use docker-sync instead of mounted volumes: https://docker-sync.readthedocs.io/en/latest/getting-started/installation.html


# Deployment

tbd.



