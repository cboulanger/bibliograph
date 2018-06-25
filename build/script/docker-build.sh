#!/usr/bin/env bash
docker build -f build/env/docker/Dockerfile -t cboulanger/bibliograph:$(git describe --tags) .