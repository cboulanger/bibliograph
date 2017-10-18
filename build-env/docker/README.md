Bibliograph Docker Image
========================

This is a preconfigured image of the web-based bibliographic data manager [Bibliograph](http://www.bibliograph.org) 
running in an Ubuntu container. It's an easy way to try the application out and see whether it 
provides what you need. The docker setup is simple and should not be used in production. 
Improvements are very welcome.

The container uses the latest [master branch at GitHub](https://github.com/cboulanger/bibliograph/tree/master)

Building and running of the Image
---------------------------------

On Mac and Windows, use [Kitematic](https://kitematic.com/) to run the image.

On Linux, or if you like the command line, download and build the container with

```
sudo docker pull cboulanger/bibliograph
sudo docker build -t cboulanger/bibliograph .
```

If you just want to test the software, run

```
sudo docker run --rm -p 80:80 cboulanger/bibliograph
```

This will remove the container and its data when you shut down the process.

For a daemonized process, run

```
sudo docker run -d -p 80:80 cboulanger/bibliograph
```

Data persistence
----------------

By default, the data of the container is insulated inside the container and gone 
when you remove the container. If you want to access or backup this data, you can a)
mount the data directories to the host, b) use a mysql server on the host to store 
the application data, or c) use a different container to store the data. Here, I only
address the first two options (even though the third option seems to be best practice).

a) If you want to access or store the container data, mount the mysql data directory
and the directory containing temporary and cached data by adding these options to your
`docker run` command:

```
docker run ... \
 -v /opt/bibliograph/mysql-data:/var/lib/mysql \
 -v /opt/bibliograph/other-data:/var/lib/bibliograph \
  ...
```

Replace /opt/bibliograph/XXX with the path to directories you want to use on the host.

NOTE: This should be working according to the Docker Docs and according to Google, 
but is NOT working in my setup (Ubuntu 15.04): the mysql server doesn't start. Any ideas?

b) If the data should not be stored in the mysql server of the container, but instead
in an existing mysql server on the host, you can set the following environment variables:

```
docker run .... \
 -e "BIB_USE_HOST_MYSQL=yes" -e "BIB_MYSQL_USER=superuser" -e "BIB_MYSQL_PASSWORD=secret" \
 ...
```
Replace "superuser" and "secret" with the real username and password of a MySql user that
is allowed to log in from everywhere and has all privileges. You might have to create such a
user first in the database and you can safely delete this newly-created user afterwards. The
user account is only needed to create the database tables and a "bibliograph" user which
is allowed to log in only from the docker container's IP address.

Configuration and use
---------------------
The image is configured to install all plugins shipped with the release.
You can log with the following credentials (username/password):

- user/user
- manager/manager
- admin/admin

Please change the password of the admin account immediately by clicking on the "Administrator" button
on the top left side and delete the "user" and "manager" accounts if you dont need them (via the [Access Control Tool](https://sites.google.com/a/bibliograph.org/docs-v2-en/administration/access-control)).

Issues:
-------
- https-access on port 443 doesn't work yet. 

If you can improve the docker setup, fork the code and share an improved version.
In particular, SSL support is needed:  through a user-supplied certificate with 
a fallback to a self-signed, automatically generated certificate.
