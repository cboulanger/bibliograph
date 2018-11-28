#!/usr/bin/env bash
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