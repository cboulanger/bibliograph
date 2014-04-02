Bibliograph Online Reference Manager
====================================

Bibliograph is a powerful open source web application for the collaborative collection, editing and publishing of
bibliographic data. See http://www.bibliograph.org

Features
--------
- TODO

Prerequisites
-------------
- PHP 5.3
- MySql 5.3+ with the following extensions: gettext, yaz/xsl (optional), ldap (optional), zip (optional)

Preparations
------------
- Rename services/config/bibliograph.ini.dist.php in services/config/bibliograph.ini.php
- Create a user "bibliograph" in your MySql-database with password "bibliograph" (if you want to use a different password for security, enter it in the [database] section of bibliograph.ini.php.
- Create the following databases: "bibliograph_admin", "bibliograph_tmp", "bibliograph_user". If you want to use different names or use only one database, adapt the settings in the [database] section of bibliograph.ini.php.
- Give the bibliograph user ALL rights for these databases
- Rename services/config/server.conf.dist.php in services/config/server.conf.php
- Enter the email address of the administrator of the installation in the [admin.email] section
  in services/config/bibliograph.ini.php

Optional:
- to import from library databases, you need to install the php YAZ extension (http://www.indexdata.com/phpyaz):
  https://code.google.com/p/list8d/wiki/InstallingYaz
  and the php-xsl extension (Debian: apt-get install php5-xsl)
- to enable export and import of various bibliographic data formats, install the bibutils toolset
  (Debian: apt-get install bibutils) and adapt the BIBUTIBibliograph Online Reference Manager
====================================

Bibliograph is a powerful open source web application for the collaborative collection, editing and publishing of
bibliographic data. See http://www.bibliograph.org

Features
--------
- TODO

Prerequisites
-------------
- PHP 5.3
- MySql 5.3+ with the following extensions: gettext, yaz/xsl (optional), ldap (optional), zip (optional)

Preparations
------------
- Rename services/config/bibliograph.ini.dist.php in services/config/bibliograph.ini.php
- Create a user "bibliograph" in your MySql-database with password "bibliograph" (if you want to use a different
  password for security, enter it in the [database] section of bibliograph.ini.php.
- Create the following databases: "bibliograph_admin", "bibliograph_tmp", "bibliograph_user". If you want to use
  different names or use only one database, adapt the settings in the [database] section of bibliograph.ini.php.
- Give the bibliograph user ALL rights for these databases
- Rename services/config/server.conf.dist.php in services/config/server.conf.php
- Enter the email address of the administrator of the installation in the [admin.email] section
  in services/config/bibliograph.ini.php

Optional
--------
- to import from library databases, you need to install the php YAZ extension (http://www.indexdata.com/phpyaz):
  https://code.google.com/p/list8d/wiki/InstallingYaz
  and the php-xsl extension (Debian: apt-get install php5-xsl)
- to enable export and import of various bibliographic data formats, install the bibutils toolset
  (Debian: apt-get install bibutils) and adapt the BIBUTILS_PATH constant in config/server.conf.php
- if you want to allow backups, install the php zip extension and grant the global "RELOAD" privilege to the
 "bibliograph"
  user. if the backups should not be stored in the system tempdir, adapt the BIBLIOGRAPH_BACKUP_PATH constant in
  config/server.conf.php.
- you can connect a ldap server for authentication (adapt config/bibliograph.ini.php)

Building & Deployment
-----------------------
- Building the application requires the qooxdoo library (currently, version 2.1).
- Download the sdk from http://sourceforge.net/projects/qooxdoo/files/qooxdoo-current/2.1.2/
- Unzip the sdk into a top-level "qooxdoo" folder and rename it to "2.1". You can also adapt the path to the sdk in the
  bibliograph/config.json configuration file
- Issue "./generate build" in the "bibliograph" folder.
- For deployment, you only need the bibliograph/build and bibliograph/services folders, the rest is only neccessary to
  build the app
- Securing the Server: The PHP backend has one single entry-point: services/server.php. If you want to make sure no
  other PHP script is called from outside, restrict access to php files to this path.
- It is recommended to create a redirection from the top-level path to the bibliograph/build folder

First run
---------
- open "build/index.html" folder in your browser and click somewhere outside the splash screen to make it disappear.
- A message "Setup in progress..." appears. Click "OK" and wait.
- A message "Setup has finished. Reload the application" is displayed.
- Reload and login as "Admin"/"admin"
- Got to System -> Plugins. Install the YAZ and bibutils plugins if you have enabled them.
- Reload the application.
LS_PATH constant in config/server.conf.php
- if you want to allow backups, install the php zip extension and grant the global "RELOAD" privilege to the
 "bibliograph"
  user. if the backups should not be stored in the system tempdir, adapt the BIBLIOGRAPH_BACKUP_PATH constant in
  config/server.conf.php.
- you can connect a ldap server for authentication (adapt config/bibliograph.ini.php)

Built-step & Deployment
-----------------------
- Building the application requires the qooxdoo library (currently, version 2.1). Issue "./generate build" in the
  "bibliograph" folder.
- For deployment, you only need the bibliograph/build and bibliograph/services folders, the rest is only neccessary to build the app
- Securing the Server: The PHP backend has one single entry-point: services/server.php. If you want to make sure no other PHP script is called from outside, restrict access to php files to this path.
- It is recommended to create a redirection from the top-level path to the bibliograph/build folder

First run
---------
- open "build/index.html" folder in your browser and click somewhere outside the splash screen to make it disappear.
- A message "Setup in progress..." appears. Click "OK" and wait.
- A message "Setup has finished. Reload the application" is displayed.
- Reload and login as "Admin"/"admin"
- Got to System -> Plugins. Install the YAZ and bibutils plugins if you have enabled them.
- Reload the application.
