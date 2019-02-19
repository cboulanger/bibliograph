#!/usr/bin/env bash

# Compile-time dependencies (Composer, NPM)
if [[ "$PHPVERSION" == "7.0" ]]; then export COMPOSER=$(pwd)/install/php7.0/composer.json; fi
if [[ -f package-lock.json ]]; then rm package-lock.json; fi
npm install
