Bibliograph Online Reference Manager
====================================

Bibliograph is a powerful open source web application for the collaborative collection, editing and publishing of
bibliographic data.

- Demo installation: http://demo.bibliograph.org
- Documentation: http://www.bibliograph.org

Bibliograph

- is an application that lets you collect, edit, and publish bibliographic data collaboratively on the web.
- has a modern and intuitive user interface that makes the daily life of working with bibliographies and library collections easy and fun.
- allows researchers, librarians, teachers and students work together online without having to install software locally.
- is fully open source and free to download and install.

Bibliograph can be used by

- scholars and librarians who want to publish a book collection or a thematic bibliography publicly online
- groups of researchers who work together in a research project and want to collect and share bibliographic references
- professors and teachers who want to share bibliographic information with their students

Features
--------
- Organize bibliographic records in static folders or dynamic collections based on queries
- Rich metadata, Autocompletion and duplicate detection
- Allows natural language queries like "title contains hamlet and author beginswith shake"
- Fine-grained access control system with users, roles, groups and permissions allows flexible user management and contol of who is allowed to view, enter, edit and delete data.
- Unlimited amount of separate databases
- Imports data from library catalogues (through Z39.50 interface) and from various file-based data formats (RIS, BibTeX, Endnote, MODS, and more)
- Export data into open formats
- Formats bibliographic records with various citation styles (APA, Chicago, ...) using CSL templates and the citeproc style processor (http://citationstyles.org)
- Can create backup snapshots of databases and
- LDAP integration to connect to existing LDAP servers
- Fully open source, can be easily adapted and extended
- Extensible data model allows easy modification of record fields and integration of a variety of backends (e.g., NoSql, xml, REST or binary backends)

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
- To import from library databases, you need to install the php YAZ extension (http://www.indexdata.com/phpyaz):
  https://code.google.com/p/list8d/wiki/InstallingYaz
  and the php-xsl extension (Debian: apt-get install php5-xsl)
- To enable export and import of various bibliographic data formats, install the bibutils toolset
  (Debian: apt-get install bibutils) and adapt the BIBUTILS_PATH constant in config/server.conf.php
- If you want to allow backups, install the php zip extension and grant the global "RELOAD" privilege to the
 "bibliograph" user. if the backups should not be stored in the system tempdir, adapt the BIBLIOGRAPH_BACKUP_PATH
  constant in config/server.conf.php and point it to a world-writable folder outside the document root of the web server.
- You can connect a ldap server for authentication (adapt config/bibliograph.ini.php)

Building & Deployment
-----------------------
- Building the application requires the qooxdoo library (version 3.5).
- Download the sdk from http://sourceforge.net/projects/qooxdoo/files/qooxdoo-current/
- Unzip the sdk into a top-level "qooxdoo" folder. You can also adapt the path to the sdk in the bibliograph/config.json
  configuration file.
- Issue "./generate build" in the "bibliograph" folder.
- For deployment, you only need to copy the bibliograph/build and bibliograph/services folders to the production server.
  The rest is only necessary to build the application.
- Securing the Server: The PHP backend has one single entry-point: services/server.php. If you want to make sure no
  other PHP script is called from outside, restrict access to php files to this path.
- It is recommended to create a redirection from the top-level path to the bibliograph/build folder
- By default, Bibliograph stores persistent data in the system temporary folder (on Linux, this is usually /tmp). This
  is fine for testing the application, but can lead to the loss of data whenever this folder is automatically cleaned
  up by the OS. For permanent production installations, you MUST change the QCL_VAR_DIR constant in
  services/config/server.conf.php to a world-writable directory outside the document root of the web server.

First run
---------
- open "build/index.html" folder in your browser and click somewhere outside the splash screen to make it disappear.
- A message "Setup in progress..." appears. Click "OK" and wait.
- A message "Setup has finished. Reload the application" is displayed.
- Reload and login as "Admin"/"admin"
- Got to System -> Plugins. Install the YAZ and bibutils plugins if you have enabled them.
- If you want to use the system in production, change the password of the "Admin" user, delete the "Manager" and "User"
  users and configure your own users in the System > Acces Control tool.
- Reload the application.


