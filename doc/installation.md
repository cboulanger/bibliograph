# Installation

## Prerequisites

Bibliograph should work on any platform that can run an [officially supported
version of PHP](https://www.php.net/supported-versions.php) and a Webserver.
However, testing and development is done with Debian & Ubuntu & MacOS, and I can
only provide support for these platforms. 

Because Bibliograph makes heavy use of PHP extensions, root access
to the server is required. Hosting Bibliograph on shared Webspace
without root access might be possible, but hasn't been tested.

The prerequisites are:

- A weberver such as Apache configured to run PHP
  
- PHP (>=v7.3) with PEAR and with the following extensions: 
    - dev, mysql, (ldap), curl, gd, intl, mbstring, xml, xsl, (yaz), zip
    - ldap is required only if you want to connect an LDAP server
    - yaz is required only if you want to import from library catalogues
  
- bibutils, (jq): installable via Linux package managers
or Homebrew on MacOS. `jq` is only required for development.
  
- A dedicated MySQL/MariaDB database and user account. The user
must have all privileges for the database. It is also possible, but
not recommended, to use a shared database by setting a table prefix.

## Install scripts

You can find install scripts for Debian and Ubuntu [here](/tool/install/).
They should give you an idea which packages and libraries are
required, so you can adapt them for your particular server environment.

If you have managed to install the software on a different
platform, please consider contributing the installation script.


