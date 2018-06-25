# Bibliograph - Online Bibliographic Data Manager
# Build the latest GitHub master
# todo: use MarvAmBass/docker-apache2-ssl-secure or similar image as base

FROM ubuntu:14.04
MAINTAINER Christian Boulanger <info@bibliograph.org>

ENV DEBIAN_FRONTEND noninteractive

# Packages
RUN apt-get update && apt-get install -y \
  supervisor apache2 libapache2-mod-php5 php5-cli \
  mysql-server php5-mysql \
  bibutils \
  php5-dev php-pear \
  wget \
  php5-xsl php5-intl\
  yaz libyaz4-dev \
  zip \
  git

# Install php-yaz
RUN pecl install yaz && \
  pear install Structures_LinkedList-0.2.2 && \
  pear install File_MARC && \
  echo "extension=yaz.so" >> /etc/php5/apache2/php.ini && \
  echo "extension=yaz.so" >> /etc/php5/cli/php.ini

# enable SSL, not working
RUN /bin/ln -sf ../sites-available/default-ssl /etc/apache2/sites-enabled/001-default-ssl && \
  a2enmod ssl && a2enmod socache_shmcb
  
# Environment variables for the setup
ENV DOCKER_RESOURCES_DIR=build/environments/docker
ENV BIB_VAR_DIR /var/lib/bibliograph
ENV BIB_DEPLOY_DIR /var/www/html
ENV BIB_CONF_DIR /var/www/html/bibliograph/services/config/
ENV BIB_USE_HOST_MYSQL no
ENV BIB_MYSQL_USER root
ENV BIB_MYSQL_PASSWORD secret

# checkout the bibliograph's master branch on GitHub and build qooxdoo app
RUN rm -rf /var/www/html/* && \
  git clone https://github.com/cboulanger/bibliograph.git && \
  cd bibliograph && \
  git clone https://github.com/qooxdoo/qooxdoo.git && \
  cd bibliograph && \
  ./generate.py -I build && \
  cd .. && \
  mv bibliograph /var/www/html && \
  cd ../.. && \
  echo "<?php header('location: /bibliograph/build');" > $BIB_DEPLOY_DIR/index.php && \
  mkdir -p $BIB_VAR_DIR && chmod 0777 $BIB_VAR_DIR && \
  echo "all" > $BIB_DEPLOY_DIR/bibliograph/plugins.txt
  
# add configuration files
COPY $DOCKER_RESOURCES_DIR/app.conf.toml $BIB_CONF_DIR/app.conf.toml
COPY $DOCKER_RESOURCES_DIR/server.conf.php $BIB_CONF_DIR/server.conf.php

# supervisor files
COPY supervisord-apache2.conf /etc/supervisor/conf.d/supervisord-apache2.conf
COPY supervisord-mysqld.conf /etc/supervisor/conf.d/supervisord-mysqld.conf

# add mysqld configuration
COPY my.cnf /etc/mysql/conf.d/my.cnf

# Start command
COPY run.sh /run.sh
COPY start-apache2.sh /start-apache2.sh
COPY start-mysqld.sh /start-mysqld.sh

# Expose ports
EXPOSE 80 443

# Run
RUN chmod 755 /*.sh
CMD ["/run.sh"]
