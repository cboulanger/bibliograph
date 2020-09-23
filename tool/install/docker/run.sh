#!/bin/bash

echo
CLIENT_IP=$(hostname --ip-address)

# fix mysql setup
find /var/lib/mysql -type f -exec touch {} \; # see https://github.com/docker/for-linux/issues/72

echo "Waiting for MySql database to start..."
/usr/bin/mysqld_safe & #> /dev/null 2>&1 &
RET=1
while [[ RET -ne 0 ]]; do
    echo -n "."
    sleep 5
    mysql -uroot -e "status" #> /dev/null 2>&1
    RET=$?
done
AUTH_ARGS="-uroot"
GRANTEE="'bibliograph'"

mysql $AUTH_ARGS -e "CREATE DATABASE bibliograph;"
mysql $AUTH_ARGS -e "GRANT ALL PRIVILEGES ON \`bibliograph\`.* TO $GRANTEE IDENTIFIED BY 'bibliograph' WITH GRANT OPTION;"
mysqladmin -uroot shutdown

echo
echo "Server ready." 

exec supervisord -n