# Installation and Deployment

## Debian/Ubuntu
- In a vanilla Debian installation (for example, in the official Debian Docker image), do
- `apt-get update -y && apt-get upgrade -y && apt-get install git`
- `git clone https://github.com/cboulanger/bibliograph.git`
- `cd bibliograph`
- `git fetch && git checkout develop`
- `bash build-env/debian-ubuntu/install.sh`.

## Manual installation 

### Prerequisites
- PHP >= 7.0 with the following extensions: intl, gettext, yaz/xsl (optional), 
  ldap  (optional), zip (optional). For optimal performance, it is advised to enable 
  OPcache (http://php.net/manual/en/intro.opcache.php)
- MySql >= 5.3 
- A web server, such as apache, or use the built-in server with `php -S`.

### Installation 
- At them moment, there is no built package available. You'll have to clone the repository
  and replicate the steps in [the Debian/Ubuntu install script](build-env/debian-ubuntu/install-deb-ubuntu.sh) adapted to your local environment.

### Post-Installation 
- In `src/server/config/`, rename `bibliograph.ini.dist.php` to
  `bibliograph.ini.php`
- Create a user "bibliograph" in your MySql-database with password "bibliograph", or,
  if you want to use a different username and password (for example, if your database
  provider assigns you fixed credetials), enter the values in the [database] section 
  of bibliograph.ini.php.
- Create a database called "bibliograph". If you want to use a different names or use   
  different databases to separate admnistrative, bibliographic and temporary tables, 
  adapt the settings in the [database] section of bibliograph.ini.php.
- Give the bibliograph user ALL rights for these databases
- Enter the email address of the administrator of the installation in the 
  [admin.email] section in `services/config/bibliograph.ini.php`

### Optional post-install steps
- You can connect a ldap server for authentication (adapt the settings in the `[ldap]` section of `src/server/config/bibliograph.ini.php`)

## Test-Run


## Deployment