# Development

> Note: Developing on Windows is currently not supported. Please use a Linux or MacOS environment.

## Setup development environment

In order to be able to develop Bibliograph with the same tools regardless of the development environment, 
a dockerized setup is used. You need the following prerequisites: 

 - Docker (See installation instructions on https://www.docker.com/)
 - wget (on MacOs, use Homebrew and do `brew install wget`).
 
Execute `tool/env/install` in the top level directory. 

This will provide you with the following dockerized services running in separate containers:
 - PHPFarm with php versions 7.0 - 7.4
 - MariaDB
