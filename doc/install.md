# Installation and Deployment

Note: Bibliograph is not yet compatible with PHP >=7.0. We're [working on it](https://github.com/cboulanger/bibliograph/tree/branch_php7). You are welcome to create pull requests ([See why it fails](https://travis-ci.org/cboulanger/bibliograph/branches)). 

## Download
You can download a precompiled package from [SourceForge](http://sourceforge.net/projects/bibliograph/files/latest/download), but this requires
that you install all dependencies manually (see below).

## Build environments
You can also build the application yourself. Bibliograph comes with support for a 
couple of different build environments (see the `build-env` folder), so that you don't 
have to deal with the dependencies.

### Docker
The easiest way to install Bibliograph is by using the [preconfigured docker image](https://registry.hub.docker.com/u/cboulanger/bibliograph/). Note, however, that 
this image is currently meant only for testing purposes. If you need a production 
setup, you must create your own installation.

### Cloud9IDE
The application can be easily cloned from GitHub to the [web-based Cloud9 IDE](https://c9.io), in fact this is how much of the development has been done recently. Create a new VM based on your own fork of the [GitHub repository](https://github.com/cboulanger/bibliograph). Once the VM 
starts, in the terminal, execute `bash build-env/c9.io/install-c9.sh`. Then start the
Apache/PHP run configuration and you should be all set!

### Debian/Ubuntu
Note: this setup hasn't been tested thoroughly yet and might be buggy.
After cloning the repository, run `bash build-env/debian-ubuntu/install-deb-ubuntu.sh`.
You'll still have setup apache configuration, though. 

# Manual installation 

## Prerequisites
- PHP >= 5.3 < 7.0 with the following extensions: intl, gettext, yaz/xsl (optional), 
  ldap  (optional), zip (optional). For optimal performance, it is advised to enable 
  OPcache (http://php.net/manual/en/intro.opcache.php)
- MySql >= 5.3 . There have been some problems with the latest MySql Versions. Stay with
  v5.3 if you can for the moment. 

## Install steps 
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

## Optional post-install steps
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

## First run
- Fire up a browser and open the "build" folder. You should see a popup window with 
  a progress bar and a textfield with information on the ongoing setup. 
- If problems occur, error messages will be displayed and the setup will be aborted 
  (see troubeshooting guide below).
- After the setup has fininished, reload the page and login as "Admin"/"admin"
- Got to System -> Plugins. Install the plugins you need.
- Reload and you should be all set.

## Troubleshooting
- If you encounter SQL/Database related error messages during or after the first 
  run, try first to reset the application by replacing, in the URL, the part starting
  wit `build` up to the end of the URL, with `reset.php`. This will clear some
  cache and reset session and cookies, and might resolve the problem when reloading
  the application.
- If you are able to load the application and log in as administrator, you can 
  install the "debug" plugin, open a window with the application backend log, and
  increase the verbosity of the log messages by selecting log filters. 
- Otherwise, look at the server log (the location is set in server.conf.php), it should
  contain the error messages together with a backtrace. 
- If you cannot solve the problem, I am happy to help. The better you prepare your
  issue, the easier it is for me to help you quickly. 
  1. Ideally, create an [issue on GitHub](https://github.com/cboulanger/bibliograph/issues)    and save the server log in a separate Gist so that it does not blow up the issue. 
     This way, others can profit from the resolution.
  2. You can also send an email to info@bibliograph.org . If the problem is more than
     trivial, I might ask you to follow 1) 

# Production deployment
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
