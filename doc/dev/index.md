# Development

Bibliograph is Open Source software - you are welcome to adapt
it for your needs and make it better. Simply create a fork
on GitHub, check it out locally and start hacking away...

## Setup development environment

In order to be able to develop Bibliograph with the same
tools regardless of the development environment, a dockerized
setup is used. You need the following prerequisites:

 - Docker - See installation instructions on https://www.docker.com
   On the Mac, make sure to use Docker Desktop >= v2.3.3.0, otherwise
   the performance of MariaDB/MySql is abysmal.
   
 - NodeJS, latest LTS - It is suggested to use [nvm](https://github.com/nvm-sh/nvm).
   
 - [PNPM](https://github.com/pnpm/pnpm) - Install with `npm install -g pnpm` - you can also use NPM, if you prefer.
 
First, run `pnpm install` to install all needed NPM modules.
Then, run `pnpm run install` to set up the docker containers
with the development environment. This will provide you with the
following dockerized services running in separate containers:

 - PHPFarm with php versions 7.0 - 7.4 via Apache on localhost:8070-8074 (8.x will be added shortly) 
   
 - MariaDB on localhost:3036
 
To start the services, run `pnpm run services:start`. Afterwards,
you need to run `pnpm run dev:clean` to run the application in
development mode for the first time, setting up the MariaDB server
beforehand. If you are on a Mac, this will also open the application
in a new Google Chrome window together with its developer tools.

This also starts the continuous compilation process which will
update the application bundle that is loaded into the browser
each time you change anything in the frontend code. You will
have to reload the application to see the changes, however.

To restart the application and continous compilation without
resetting the backend data, run `pnpm run dev`.

> Sometimes, and for unknown reasons, changed backend code isn't properly
synchronized and the Apache/PHP server still executes a previous
version of the code. If you feel that this happens, execute `pnpm run
services:apache:restart`, which restarts Apache and will force it to use the new state. 

If you want to restart the continuous compilation process
without opening a browser window, use `pnpm run compile:watch`.

## Testing code changes

The PHP backend is covered by a fairly extensive test suite which can be run with
`pnpm runn test:codeception:all`. Any changes to the backend should be checked by
running this suite, and any non-trivial addition to it should come with an individual
test for each new feature. You can also run unit, functional and API tests separately
with `pnpm runn test:codeception:(unit|functional|api)`.

## Deployment

## Publishing a new version


## To do
 - use docker-sync instead of mounted volumes: https://docker-sync.readthedocs.io/en/latest/getting-started/installation.html
