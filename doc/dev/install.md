# Installation 

## User installation

- todo

## Manual installation for development

### Prerequisites
- PHP >= 7.0 with the following extensions: intl, gettext, yaz/xsl (optional), 
  ldap  (optional), zip (optional). For optimal performance, it is advised to enable 
  OPcache (http://php.net/manual/en/intro.opcache.php)
- MySQL v5.x (>= v5.3). MySQL v8 is backwards-incompatible and currently not supported.
- A web server, such as apache, or use the built-in server with `php -S`.

### Installation
- The latest prebuilt package of the current beta releases can be 
  [downloaded from GitHub](https://github.com/cboulanger/bibliograph/releases/).
  Unpack, follow the post-installation instructions, and you should be ready to.
- To install a development environment, see the instructions [here](../../tool/install/)

### Post-Installation 
- In `src/server/config/`, rename `app.conf.toml.dist` to `app.conf.toml`
- Create a user "bibliograph" in your MySql-database with password "bibliograph", or,
  if you want to use a different username and password (for example, if your database
  provider assigns you fixed credetials), enter the values in the [database] section 
  of app.conf.toml.
- Create a database called "bibliograph". If you want to use a different names or use   
  different databases to separate admnistrative, bibliographic and temporary tables, 
  adapt the settings in the [database] section of app.conf.toml.
- Give the bibliograph user ALL rights for these databases
- Enter the email address of the administrator of the installation in the 
  [email] section in `app.conf.toml`

### Optional post-install steps
- You can connect a ldap server for authentication (adapt the settings in the `[ldap]` section of 
  `src/server/config/app.conf.toml`)
