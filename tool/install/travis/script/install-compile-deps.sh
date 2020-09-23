#!/usr/bin/env bash

# Compile-time dependencies (Composer, NPM)
PHPVERSION=$(phpenv version-name)
if [[ -f package-lock.json ]]; then rm package-lock.json; fi
npm install
