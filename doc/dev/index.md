# Development

## Setup development environment

In order to be able to develop Bibliograph with the same tools regardless of the development environment, 
a dockerized setup is used. You need the following prerequisites: 

 - Docker - See installation instructions on https://www.docker.com
 - NodeJS, latest LTS - It is suggested to use [nvm](https://github.com/nvm-sh/nvm).
 
First, run `npm install` to install all needed NPM modules.

Then run `ntl` to get a list of available commands. Choose "env:install › Install the development environment"

This will provide you with the following dockerized services running in separate containers:
 - PHPFarm with php versions 7.0 - 7.4 via Apache on localhost:8070-8074
 - MariaDB on localhost:3036
 
Afterwards, run `ntl` again and choose "Start webserver and database server". Alternatively, you can manually
start the servers by executing `npm run env:servers:start`.


 


