Installation and Deployment
===========================

Docker
------
The easiest way to install Bibliograph is by using the [preconfigured docker image](https://registry.hub.docker.com/u/cboulanger/bibliograph/). 

Caveat: this image is currently meant only for testing purposes. If you need a 
production set up, you have to build your own installation, using the instructions
below. It might be useful to look at the Ubuntu-based [Dockerfile](https://github.com/cboulanger/bibliograph-docker/blob/master/Dockerfile).

Prerequisites
-------------
- PHP >= 5.3 with the following extensions: intl, gettext, yaz/xsl (optional), ldap
  (optional), zip (optional). For optimal performance, it is advised to enable OPcache
  (http://php.net/manual/en/intro.opcache.php)
- MySql >= 5.3 

Preparations
------------
- Rename `services/config/bibliograph.ini.dist.php to
  `services/config/bibliograph.ini.php`
- Create a user "bibliograph" in your MySql-database with password "bibliograph", or,
  if you want to use a different username and password (for example, if your database
  provider assigns you fixed credetials), enter the values in the [database] section 
  of bibliograph.ini.php.
- Create the following databases: "bibliograph_admin", "bibliograph_tmp", 
  "bibliograph_user". If you want to use different names or use only one database, 
  adapt the settings in the [database] section of bibliograph.ini.php.
- Give the bibliograph user ALL rights for these databases
- Rename `services/config/server.conf.dist.php` to `services/config/server.conf.php`
- Enter the email address of the administrator of the installation in the 
  [admin.email] section in `services/config/bibliograph.ini.php`

Optional
--------
- To import from library databases, you need to [install](https://code.google.com/p/list8d/wiki/InstallingYaz) the [PHP-YAZ extension](http://www.indexdata.com/phpyaz)
  and the php-xsl extension (Debian: apt-get install php5-xsl)
- To enable export and import of various bibliographic data formats, install the 
  bibutils toolset (Debian: apt-get install bibutils) and adapt the BIBUTILS_PATH 
  constant in `config/server.conf.php`.
- If you want to allow backups, install the php zip extension and grant the global 
  "RELOAD" privilege to the "bibliograph" user. if the backups should not be 
  stored in the system tempdir, adapt the BIBLIOGRAPH_BACKUP_PATH
  constant in config/server.conf.php and point it to a world-writable folder 
  outside the document root of the web server.
- You can connect a ldap server for authentication (adapt `config/bibliograph.ini.php`)

First run
---------
- fire up a browser and open the "build" folder. If problems with the setup 
  occur, error messages will be displayed and will tell you to fix the problems.
- After the setup has fininished, reload the page and login as "Admin"/"admin"
- Got to System -> Plugins. Install the plugins you need.
- Reload and you should be all set.

Deployment
----------
- Securing the Server: The PHP backend has one single entry-point: 
  `services/server.php`. If you want to make sure no other PHP script is called,
  restrict access to php files to this path.
- It is recommended to create a redirection from the top-level path to the 
  bibliograph/build folder
- By default, Bibliograph stores persistent data in the system temporary folder 
  (on Linux, this is usually `/tmp`). This is fine for testing the application,
  but can lead to the loss of data whenever this folder is automatically cleaned
  up by the OS. For permanent production installations, you MUST change the 
  QCL_VAR_DIR constant in `services/config/server.conf.php` to a world-writable
  directory outside the document root of the web server.
- Before using the software in a production environment, change the password of 
  the "Admin" user, delete the "Manager" and "User" users and configure your own 
  users in the System > Acces Control tool.
- Change the access.enforce_https_login preference in `config/bibliograph.ini.php`
  to "yes" so that passwords are not sent in plain text.
- Change the QCL_APPLICATION_MODE constant in `config/server.conf.php` to
  "production". When you need to apply updates, change it back to "maintenance".
  Note: this is a security feature that doesn't do anything at the moment, but
  might be used to prevent configuration changes in 'production' mode. 
