# Dockerfile of Bibliograph - Online Bibliographic Data Manager
#
# This Dockerfile is part of the Travis-CI build. It is not self-contained, but relies on a
# built version of the software in the "dist" folder.

FROM ubuntu:18.04
MAINTAINER Christian Boulanger <info@bibliograph.org>
ENV DEBIAN_FRONTEND noninteractive

# Packages
RUN apt-get update && apt-get install -y \
  bibutils wget zip \
  supervisor \
  apache2 libapache2-mod-php \
  mysql-server \
  php php-cli php-intl php-xsl php-mbstring php-mysql php-zip php-dev php-pear \
  yaz libyaz5-dev

# Install php-yaz
RUN \
  pecl config-set php_ini /etc/php/7.2/apache2/php.ini && \
  pear config-set php_ini /etc/php/7.2/apache2/php.ini && \
  pecl install yaz && \
  pear channel-update pear.php.net && \
  pear install Structures_LinkedList-0.2.2 && \
  pear install File_MARC

# Environment variables for the setup. Leave here so changes won't invalidate the cache
ENV DOCKER_RESOURCES_DIR=build/env/docker
ENV BIB_DIST_DIR=dist
ENV BIB_VAR_DIR /var/lib/bibliograph
ENV BIB_DEPLOY_DIR /var/www/html

# copy dist directory, remove unneeded files
COPY $BIB_DIST_DIR/ $BIB_DEPLOY_DIR/
# RUN rm $BIB_DEPLOY_DIR/*.zip

# add configuration files
COPY $DOCKER_RESOURCES_DIR/app.conf.toml $BIB_DEPLOY_DIR/server/config/
# RUN chmod -R 0777 $BIB_DEPLOY_DIR/server/runtime/*

# supervisor files
COPY $DOCKER_RESOURCES_DIR/supervisord-apache2.conf /etc/supervisor/conf.d/supervisord-apache2.conf
COPY $DOCKER_RESOURCES_DIR/supervisord-mysqld.conf /etc/supervisor/conf.d/supervisord-mysqld.conf

# add mysqld configuration
COPY $DOCKER_RESOURCES_DIR/my.cnf /etc/mysql/conf.d/my.cnf
RUN mkdir -p /var/run/mysqld && chown mysql:mysql /var/run/mysqld

# Start command
COPY $DOCKER_RESOURCES_DIR/run.sh /run.sh
COPY $DOCKER_RESOURCES_DIR/start-apache2.sh /start-apache2.sh
COPY $DOCKER_RESOURCES_DIR/start-mysqld.sh /start-mysqld.sh

# Expose ports
EXPOSE 80

# Run
RUN chmod 755 /*.sh
CMD ["/run.sh"]
