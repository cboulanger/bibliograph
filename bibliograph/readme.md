=== Bibliograph Online Reference Manager ===

Bibliograph is a powerful open source web application for the collaborative collection, editing and publishing of
bibliographic data.

== Features ==
- TODO

== Prerequisites ==

- PHP 5.3
- MySql 5.3+ with the following extensions: gettext, yaz/xsl (optional), ldap (optional), zip (optional)

== Preparations ==

- Create a user "bibliograph" in your MySql-database
- Create the following databases: "bibliograph_admin", "bibliograph_tmp", "bibliograph_user".
- Give the bibliograph user ALL rights for these databases
- Rename services/config/bibliograph.ini.dist.php in services/config/bibliograph.ini.php
- If the password of the "bibliograph" user is not "bibliograph", adapt it.
- Rename services/config/server.conf.dist.php in services/config/server.conf.php
- Enter the email address of the administrator of the installation in the [admin.email] section
  in services/config/bibliograph.ini.php

Optional:
- to import from library databases, you need to install the php YAZ extension (http://www.indexdata.com/phpyaz):
  https://code.google.com/p/list8d/wiki/InstallingYaz
  and the php-xsl extension (Debian: apt-get install php5-xsl)
- to enable export and import of various bibliographic data formats, install the bibutils toolset
  (Debian: apt-get install bibutils) and adapt the BIBUTILS_PATH constant in config/server.conf.php
- if you want to allow backups, install the php zip extension and grant the global "RELOAD" privilege to the "bibliograph"
  user. if the backups should not be stored in the system tempdir, adapt the BIBLIOGRAPH_BACKUP_PATH constant in
  config/server.conf.php.
- you can connect a ldap server for authentication (adapt config/bibliograph.ini.php)

== Building from Sources ==

- Building the application requires the qooxdoo library (currently, version 1.5). Issue "./generate build" in the
  top "bibliograph" folder.

== First run ==

- open "build/index.html" folder in your browser and click somewhere outside the splash screen to make it disappear.
- A message "Setup in progress..." appears. Click "OK" and wait.
- A message "Setup has finished. Reload the application" is displayed.
- Reload and login as "Admin"/"admin"
- Got to System -> Plugins. Install the YAZ and bibutils plugins if you have enabled them.
- Reload the application.

== Securing the server ==

- The PHP backend has one single entry-point: services/server.php. If you want to make sure no other PHP script
  is called from outside, restrict access to php files to this path.