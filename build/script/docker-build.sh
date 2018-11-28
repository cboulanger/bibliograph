#!/usr/bin/env bash
docker build -f install/docker/Dockerfile -t cboulanger/bibliograph:$(git describe --tags) .