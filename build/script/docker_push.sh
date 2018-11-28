#!/usr/bin/env bash
# Generates a docker image and pushes it to docker hub
PHP_TARGET_VERSION=7.2
PHP_VERSION=$(php -r "echo substr(phpversion(),0,strrpos(phpversion(),'.'));")
if [[ "$PHP_VERSION" != "$PHP_TARGET_VERSION" ]]; then
  echo "Not generating docker image for PHP $PHP_VERSION"
  exit 0
fi
echo "Generating docker image for PHP $PHP_VERSION...p"
echo " >>> reverting changes to local repo ..."
git checkout .
REPO=cboulanger/bibliograph
TAG=`if [ "$TRAVIS_BRANCH" == "master" ]; then echo "latest"; else echo $TRAVIS_BRANCH ; fi`
echo " >>> Building image $REPO:$TAG' ..."
docker build -f ./install/docker/Dockerfile -t $REPO:$TAG .
docker tag $REPO:$TAG $REPO:$(git describe --tags)
echo " >>> Pushing to Docker hub...."
echo "$DOCKER_PASSWORD" | docker login -u cboulanger --password-stdin
docker push $REPO:$TAG