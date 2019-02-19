#!/usr/bin/env bash

PHPVERSION=$(phpenv version-name)
sudo add-apt-repository -y ppa:ondrej/php && sudo apt-get update
sudo apt-get install -y php${PHPVERSION}-xsl php${PHPVERSION}-intl
