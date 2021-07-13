# Production testing and deployment

Once you've tested Bibliograph in development mode locally, or if you
simply want to build & deploy from source, you can deploy it to the target
server for testing and production deployment. 

The production server must be configured to fulfill the [software
prerequisites](/doc/installation.md#prerequisites).

The deployment process is facilitated by the `tool/deploy/deploy`
script (this only works on Linux & MacOS). You can see the
available options by running `tool/deploy/deploy --help`:

```text
Usage: tool/deploy/deploy [options]
where options are:

  --env-file, -f file           - the .env file containing configuration
                                 (defaults to .env). Must be the first parameter
                                 if others are to override the settings.
  --deploy-env-file, -F file    - (optional) A stripped-down .env file which will
                                  be copied to the deploy target
  --build-config-file, -c       - the compiler configuration file to use
                                  (defaults to compile.json)
  --build-name, -n name         - an optional name for the subfolder for the build,
                                  defaults to the name of the build target
  --build-in-debug-mode, M      - turn on debugging features in build (BUILD_DEBUG_MODE=1)
  --clean                       - clean the build directories and caches
                                  before building
  --skip-build, -k              - use the build files that already exist
  --skip-build-client           - skip building of the client
  --skip-build-server           - skip building of the server
  --skip-composer               - skip building of the composer dependencies
  --deploy-host, -H path        - the host server to deploy to via SSH, overrides
                                  DEPLOY_HOST
  --deploy-dir, -D path         - the directory on the host to deploy to,
                                  overrides DEPLOY_DIR
  --deploy-config-dir, -C path  - the path to the configuration dir, absolute or
                                  relative to the deployment dir. Defaults to
                                  'config', overrides DEPLOY_CONFIG_DIR
  --create-user, -U             - create the user DB_USER with password DB_PASSWORD,
                                  can be set with DEPLOY_DB_CREATE_USER=1
  --database, -N name           - the name of the database to use on the host,
                                  overrides DB_DATABASE
  --empty-database, -E          - empty the database (DEPLOY_EMPTY_DATABASE=1)
  --import-database, -I name    - the name of the database to import tables from
                                  (only remotely), overrides DEPLOY_IMPORT_DB_NAMES
                                  implies -E
  --backup, -b [name]           - create a backup of the database before emptying it.
                                  If a name is provided, use that name for the backup
                                  (note that any existing database with that name
                                  will be deleted). Otherwise, use the database
                                  name with an appended timestamp (only remotely).
  --drop-database-prefix, -X    - Drops all databases with this prefix. (only remotely)
  --deploy-clean-dir-prefix     - Deletes all directories in the parent folder of
                                  the deployment directory that have this prefix
  --no-deploy-clean             - do not clean files on the deploy target
                                  before deployment
  --set-env, -e VAR value       - set environment variables, overriding values
                                  loaded from --env-file or existing in environment
  --yes, -y                     - answer yes to all prompts
  --verbose, -v                 - verbose output
  --help, -h                    - show usage help
```

Not all configuration values can be set via command line arguments.
Instead, they need to be a) set as environment variables before running
this script or b) to be declared in the environment variable file specified
by `--env-file` or `--deploy-env-file`, or c) to be set with `--set-env`:

```text
   DB_TYPE           - The type of the database (only mysql supported at this point)
   DB_HOST           - The host on which the database server can be reached (usually localhost)
   DB_PORT           - Port
   DB_USER           - The name of the database user for the application
   DB_PASSWORD       - Password of that user
   DB_ROOT_USER      - The name of the root user, usually "root"
   DB_ROOT_PASSWORD  - Password of root user (necessary only if DEPLOY_DB_CREATE_USER=1)
   APP_ENV_FILE      - Path to the .env file containg environment variables for the application
   APP_CONF_FILE     - Path to the .toml file containing configuration values for the application
```

Instead of providing all the options at the command line on each invocation
of the script, the configuration values should be set in an environment
variable (`.env`) file, which is passed to the script with `tool/deploy/deploy
-f /path/to/.env`. This also allows to write specialized configuration
files for about any type of deployment scenario from testing to production.

In the following example, we create three scenarios:

1. A "testing" setup, in which the app is build in "source" and debug mode
similar to the local development version. This allows to do testing and
debugging new features on the target server in a production environment

2. A "staging" setup, which is almost like the production version,
but with additional debug output, and can be used to run UI tests
against. Once this version is verified to work, we can proceed to upload

3. The user-facing "production" setup.

There are three more requirements for the deploy script to work:

1. You need to prepare a `<scenario>.conf.toml` configuration file for each
of the scenarios (see [this example](/config/example.conf.toml),
replace `<scenario>` with "testing", "staging", "production",
respectively). Make sure to use different databases for each setup!

2. You also need to put together two `.env` files; a) one `<scenario>-build.env`
file for each setup that contains the configuration values for the build
process (e.g. `testing-build.env`, which won't be part of the deployed
code, and b) one `<scenario>-deploy.env` with the environment variables for
running the app on the server (e.g. `testing-deploy.env`), which will be
copied to the target directory during the deployment process. Alternatively,
you can set the environment values for the build process in a different
way, for example, in an CI environment, by setting environment variables
beforehand. It is also possible to use just one file for building and
deployment; however, this is strongly discouraged for security reasons.
   
3. You need to set up server access via SSH in the local user's
`~/.ssh/config` so that specifying the `DEPLOY_HOST` environment
variable suffices to grant the local user write access to the
target directories on the production server. For example, if the
server is `bibliograph.example.org`, you need an entry in like so:
   
```text
Host bibliograph.example.org
Hostname bibliograph.example.org
User bibliograph
IdentityFile ~/.ssh/bibliograph@example.org.key
```

Leveraging `~/.ssh/config`'s [syntax](https://www.ssh.com/academy/ssh/config), you can 
handle more complex situations such as if the target server is behind a bastion server:

```text
Host bastion-server
Hostname bastion.example.org
User bastion
IdentityFile ~/.ssh/bastion@example.org

Host bibliograph.example.org
Hostname bibliograph.example.org
User bibliograph
IdentityFile ~/.ssh/bibliograph@example.org.key
ProxyCommand ssh -W %h:%p -q bastion-server
```

## Example: Testing Deployment

In the folllowing testing scenario, we want to start with an empty database each time the 
snapshot code is deployed to the testing installation. Alternatively, the setup could be configured 
to import the current production database ("production") or one that contains test fixtures.

`testing-build.env`
```dotenv
# Build
APP_CONF_FILE=/path/to/testing.conf.toml
BUILD_CONFIG_FILE=compile.json
BUILD_TARGET=source # would be "build" for staging/production
BUILD_DEBUG_MODE=1 # would be 0 for production
BUILD_LOGO_PATH=/path/to/logo.jpg

# Deployment
DEPLOY_HOST=bibliograph.example.org
DEPLOY_DIR=/path/to/server/webroot/bibliograph-testing # change for staging/production
DEPLOY_ENV_FILE=/path/to/testing-deploy.env # see below
DEPLOY_EMPTY_DATABASE=1 # Alternatively: DEPLOY_IMPORT_DB_NAMES=production

# Database
DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=testing # or "staging" / "production"
DB_USER=bibliograph
DB_PASSWORD=<password>

# Frontend
APP_URL=http://bibliograph.example.org/testing # change for staging/ production
```

The deployment .env file that will be copied to the server contains only
those values which are needed for running the application
(for example, it must not contain any sensitive values that are not strictly necessary):

`testing-deploy.env`
```dotenv
# Database
DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=testing
DB_USER=bibliograph
DB_PASSWORD=<password>

# Backend
BIBUTILS_PATH=/usr/bin/
BACKUP_PATH=/path/to/backup-dir
```

Once you have configured the values in your `testing-build.env` and
`testing-deploy.env`, you can start the first deployment by running

```shell
tool/deploy/deplay --env-file /path/to/testing-build.env \
  --set-env DB_ROOT_PASSWORD <MySQL/MariaDB root password> \
  --create-user
```

This will also create the database user if that account
does not already exist. Afterwards, you can simply run
`tool/deploy/deplay --env-file /path/to/testing-build.env`.

For the staging and production scenarios, you can now duplicate the
files, rename them and adapt the values. For a full list of available
environment variables, see the [script code](/tool/deploy/deploy).
