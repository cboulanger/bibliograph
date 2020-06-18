<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2018 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;


use app\models\BibliographicDatasource;
use app\models\Datasource;
use georgique\yii2\jsonrpc\exceptions\JsonRpcException;
use Illuminate\Support\Str;
use Yii;
use yii\base\Application;
use yii\db\Exception;
use Stringy\Stringy;
use app\models\Schema;
use lib\components\MigrationException;
use lib\dialog\{Dialog, Error as ErrorDialog, Confirm, Login};
use lib\exceptions\{RecordExistsException,
  ServerBusyException,
  SetupException,
  SetupFatalException,
  UserErrorException};
use lib\components\ConsoleAppHelper as Console;
use lib\Module;
use yii\helpers\Html;
use yii\web\UnauthorizedHttpException;


/**
 * Setup controller. Needs to be the first controller called
 * by the application after loading
 */
class SetupController extends \app\controllers\AppController
{
  /**
   * The name of the default datasource schema.
   * Initial value of the app.datasource.baseschema preference.
   * @todo remove
   */
  const DATASOURCE_DEFAULT_SCHEMA = BibliographicDatasource::SCHEMA_ID;

  /**
   * The name of the default bibliographic datasource class.
   * Initial value of the app.datasource.baseclass preference.
   * @todo remove
   */
  const DATASOURCE_DEFAULT_CLASS = \app\models\BibliographicDatasource::class;

  /**
   * Log category
   */
  const CATEGORY = "setup";

  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["setup", "version", "setup-version", "reset"];

  protected $errors = [];

  protected $messages = [];

  /**
   * Whether we have an ini file
   */
  protected $hasIni;

  /**
   * Whether we have a db connection
   */
  protected $hasDb;

  /**
   * Whether the user has confirmed to run the migrations
   */
  protected $migrationConfirmed = false;

  /**
   * Whether this is a new install of bibliograph without existing data.
   * @var int
   */
  protected $isNewInstallation;

  /**
   * Whether we upgrade from legacy version v2
   * @var
   */
  protected $isV2Upgrade = false;


  /**
   * Whether one of the modules was upgraded
   * @var bool
   */
  protected $moduleUpgrade = false;

  /**
   * The current version of the application as stored in the database
   * @var string
   */
  protected $upgrade_from;

  /**
   * The version in package.json, i.e. of the code, which can be higher than of
   * the data
   * @var string
   */
  protected $upgrade_to;

  /**
   * A list of setup method names
   * @var array
   */
  protected $setupMethods = [];

  /**
   * If true, execute setup methods consecutively until REQUEST_EXECUTION_THRESHOLD is reached,
   * otherwise, return response to client immediately after execution of one method.
   * @var bool
   */
  protected $batchExecuteSetupMethods = true;

  /**
   * The session id of the client that first calls this method, blocking further calls
   * @var string
   */
  protected $initiatingSessionId = null;


//-------------------------------------------------------------
  // HELPERS
  //-------------------------------------------------------------

  /**
   * Returns the names of all tables in the current database
   * @return array
   * @throws \yii\db\Exception If no connection can be established
   */
  protected function tables()
  {
    static $tables = null;
    if (is_null($tables)) {
      $dbConnect = \Yii:: $app->get('db');
      if (!($dbConnect instanceof \yii\db\Connection)) {
        throw new \yii\db\Exception('Cannot establish db connection');
      }
      $tables = $dbConnect->schema->getTableNames();
      if (!count($tables)) {
        Yii::debug("Database {$dbConnect->dsn} does not contain any tables." . implode(", ", $tables));
        return [];
      }
      Yii::debug("Tables in the database {$dbConnect->dsn}: " . implode(", ", $tables));
    }
    return $tables;
  }

  /**
   * Checks if one or more tables exist.
   * @param string|array $tableName A table name or an array of table names
   * @return bool If (all) table(s) exists in the schema
   * @throws \yii\db\Exception If no connection can be established
   */
  protected function tableExists($tableName)
  {
    $tables = $this->tables();
    if (is_array($tableName)) {
      return count(\array_diff($tableName, $tables)) == 0;
    }
    return \in_array($tableName, $tables);
  }

  /**
   * Recursively deletes files and subfolders in a given folder
   * @param string $dir
   */
  protected function emptyDir(string $dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir."/".$object))
            $this->emptyDir($dir."/".$object);
          else
            unlink($dir."/".$object);
        }
      }
    }
  }

  /**
   * Check if an ini file exists
   *
   * @throws SetupException
   */
  protected function checkIfIniFileExists()
  {
    $this->hasIni = file_exists(APP_CONFIG_FILE);
    if (!$this->hasIni) {
      if (YII_ENV_PROD) {
        throw new SetupException('Cannot run in production mode without ini file.');
      } else {
        throw new SetupException( "Wizard not implemented yet. Please add ini file as per installation instructions.");
      }
    }
    return true;
  }

  /**
   * Returns a id for caching setup data which needs to be unique and always identical.
   * Currently an MD5 hash of the class name
   * @return string
   */
  protected function cacheId() {
    return md5(self::class);
  }

  /**
   * Restore cached properties
   * @return array
   */
  protected function _restoreProperties()
  {
    $properties = unserialize(Yii::$app->cache->get($this->cacheId()));
    if (is_array($properties)) {
      //Yii::debug(">>> Restoring properties", __METHOD__);
      //Yii::debug(array_keys($properties));
      foreach ($properties as $property => $value) {
        $this->$property = $value;
      }
      return $properties;
    }
    //Yii::debug(">>> No properties saved yet", __METHOD__);
    return [];
  }

  /**
   * Save all properties of this instance to a file cache which are
   * scalar values or arrays of scalar values
   */
  protected function _saveProperties()
  {
    function is_array_with_scalars($value) {
      return is_array($value) and array_reduce($value, function($carry, $item){
        return $carry && is_scalar($item);
      }, true);
    }
    $properties = [];
    foreach( get_object_vars($this) as $property => $value) {
      if (is_scalar($value) or is_array_with_scalars($value)) {
        $properties[$property] = $value;
      }
    }
    //Yii::debug(">>> Saving properties", __METHOD__);
    Yii::$app->cache->set($this->cacheId(), serialize($properties));
  }

  /**
   * Reset file cache
   */
  protected function _resetSavedProperties()
  {
    Yii::debug("Resetting saved properties", __METHOD__);
    Yii::$app->cache->delete($this->cacheId());
  }


  //-------------------------------------------------------------
  // ACTIONS
  //-------------------------------------------------------------

  public function actionReset() {
    if (!YII_ENV_TEST) {
      throw new \BadMethodCallException('setup/reset can only be called in test mode.');
    }
    $this->_resetSavedProperties();
    return "Setup cache has been reset.";
  }

  /**
   * Returns the application verision as per package.json
   */
  public function actionVersion()
  {
    return Yii::$app->utils->version;
  }

  /**
   * Called by the confirm dialog
   * @see actionSetup()
   */
  public function actionConfirmMigrations()
  {
    $this->migrationConfirmed = true;
    return $this->actionSetup();
  }

  /**
   * The setup action. Is called as first server method from the client The result returned by
   * the server contains diagnostic messages only. More important are the messages which are
   * returned via JSON-RPC notifications:
   *
   * - bibliograph.setup.next: The setup process is not finished yet, but has ended the request to avoid a timeout.
   * The client should simply call the action again to continue the setup process.
   * - bibliograph.setup.done: The setup has completed, the client can begin to interact with the backend.
   *
   * Errors thrown: {@link JsonRpcException} with Codes {@link}
   * @throws SetupException
   * @throws ServerBusyException
   */
  public function actionSetup()
  {
    return $this->_setup();
  }

  /**
   * A setup a specific version of the application. Not allowed in production mode.
   * @see {@link SetupController::actionSetup} for details
   * @param string $upgrade_to (optional) The version to upgrade from.
   * @param string $upgrade_from (optional) The version to upgrade to.
   * @throws SetupException
   * @throws ServerBusyException
   */
  public function actionSetupVersion($upgrade_to, $upgrade_from = null)
  {
    if (!YII_ENV_TEST) {
      throw new \BadMethodCallException('setup/setup-version can only be called in test mode.');
    }
    $this->upgrade_from = $upgrade_from;
    $this->upgrade_to = $upgrade_to;
    return $this->_setup();
  }

  /**
   * The setup action. Is called as first server method from the client
   * @return string
   * @throws SetupException
   * @throws ServerBusyException
   */
  protected function _setup()
  {
    $this->_restoreProperties();
    // Abort if other client has already started the setup
    if ($this->initiatingSessionId and $this->initiatingSessionId != Yii::$app->session->id) {
      throw new ServerBusyException("Setup in progress");
    }
    $this->initiatingSessionId = Yii::$app->session->id;
    // if no setup methods have been identified, this is the start of the setup sequence
    if (count($this->setupMethods) == 0) {
      $upgrade_from = $this->upgrade_from;
      $upgrade_to = $this->upgrade_to;
      // this throws if ini file doesn't exist
      $this->checkIfIniFileExists();
      // version stored in database
      if (!$upgrade_from) {
        try {
          $upgrade_from = Yii::$app->config->getKey('app.version');
        } catch (\InvalidArgumentException $e) {
          // upgrading from version 2 where the config key doesn't exist
          $upgrade_from = "2.x";
          $this->isV2Upgrade = true;
        } catch (\yii\db\Exception $e) {
          // no tables exist yet, this is the first run of a fresh installation
          $upgrade_from = "0.0.0";
          $this->isNewInstallation = true;
        } catch (yii\base\InvalidConfigException $e) {
          // this happens deleting the tables in the database during development
          // @todo
          $upgrade_from = "0.0.0";
          $this->isNewInstallation = true;
        }
      }
      // Version in source code
      if (!$upgrade_to) {
        $upgrade_to = Yii::$app->utils->version;
      }
      $this->upgrade_from = $upgrade_from;
      $this->upgrade_to = $upgrade_to;
      if ($upgrade_to === $upgrade_from) {
        Yii::info("Starting Bibliograph v$upgrade_to ...");
        $this->dispatchClientMessage("bibliograph.setup.done", []);
        return "Setup finished. No upgrade necessary.";
      }
      // compile list of setup methods
      foreach (\get_class_methods($this) as $method) {
        if (Str::startsWith($method, "setup")) {
          $this->setupMethods[] = $method;
        }
      }
      if (count($this->setupMethods)===0) {
        throw new SetupException("No setup methods");
      }
      try {
        $userInfo = "Authenticated user: " . Yii::$app->user->identity->id;
      } catch (\Throwable $e) {
        $userInfo = "No authenticated user.";
      }

      // visual marker in log file
      Yii::debug(implode("\n", [
          "",
          "",
          str_repeat("*", 80),
          "",
          "BIBLIOGRAPH SETUP",
          "",
          str_repeat("*", 80),
          "",
          "Session ID:" . Yii::$app->session->id,
          $userInfo,
          "Data version: $upgrade_from",
          "Code version: $upgrade_to.",
          "Setup methods: \n  - " . implode("\n  - ", $this->setupMethods)
      ]), 'marker');
    }
    return $this->_runNextMethod();
  }

  /**
   * Run the next of the existing setup methods, i.e. methods of this class that have the prefix 'setup'.
   * Note the following:
   *  - Each method must be executable regardless of application setup state.
   *  - If a particular setup method encounters an error which does not prevent other methods from running,
   *    throw a lib\exceptions\SetupException. These errors will be collected and passed as data
   *    of the "bibliograph.setup.done" event sent to the client.
   *  - In case of errors that are not recoverable, throw a lib\exceptions\SetupFatalException, which will stop
   *    execution of the setup procedure.
   *  - Once all setup methods are run, the "bibliograph.setup.done" event is sent to the client
   *    with recoverable errors and messages as data.
   *  - It is possible to return control to the client by instantiating a Dialog object.
   *  - If a method is successful, a "bibliograph.setup.next" event is sent to the client, which then starts
   *    the next call to this action.
   *
   * @return mixed
   * @throws SetupException
   */
  protected function _runNextMethod()
  {
    $method = array_shift($this->setupMethods);
    Yii::debug("Calling '$method'...", self::CATEGORY);
    try {
      $result = $this->$method();
      if (!$result) {
        $result = "Setup method $method completed successfully.";
      }
    } catch (SetupException $e) {
      $result = $e->getMessage();
      Yii::error("Collecting setup exception: $result ");
      $this->errors[] = $result;
    }
    # log resulting diagnostic message
    Yii::info($result, self::CATEGORY);
    $this->messages[] = $result;
    $time = Yii::$app->log->logger->getElapsedTime();
    if (count($this->setupMethods) > 0) {
      if ($this->batchExecuteSetupMethods and  $time < REQUEST_EXECUTION_THRESHOLD) {
        // run the next method
        Yii::debug("Setup took $time seconds so far. Running next method in same request ...");
        return $result . "\n" . $this->_runNextMethod();
      } else {
        Yii::debug("Returning to client for new request to execute remaining " . count($this->setupMethods) . " methods ...");
        $this->dispatchClientMessage("bibliograph.setup.next");
        $this->_saveProperties();
      }
    } else {
      $this->finish();
    }
    return $result;
  }

  /**
   * Finishes the setup
   * @return string
   * @throws RecordExistsException
   * @throws SetupException
   */
  function finish(){
    if (count($this->errors) > 0) {
      $msg = "Setup of version '$this->upgrade_to' failed.";
      Yii::warning($msg);
      Yii::warning($this->errors);
      $this->_resetSavedProperties();
      throw new SetupException("Setup failed with errors.", $this->errors);
    }
    // Everything seems to be ok
    $msg = "Setup of version '$this->upgrade_to' finished successfully.";
    Yii::info($msg, self::CATEGORY);
    Yii::debug($this->messages, __METHOD__);
    $this->_resetSavedProperties();
    // update version
    if (!Yii::$app->config->keyExists('app.version')) {
      // createKey( $key, $type, $customize=false, $default=null, $final=false )
      Yii::$app->config->createKey('app.version', 'string', false, null, false);
    }
    Yii::$app->config->setKeyDefault('app.version', $this->upgrade_to);
    // let application know if LDAP is enabled
    $ldapEnabled =  Yii::$app->config->getIniValue("ldap.enabled");
    Yii::$app->config->setKeyDefault("ldap.enabled",$ldapEnabled);
    $this->dispatchClientMessage("bibliograph.setup.done", $this->messages);
    return $msg;
  }

  //-------------------------------------------------------------
  // SETUP METHODS
  //-------------------------------------------------------------

  /**
   * Deletes the file cache on version change
   * @throws \Exception
   */
  protected function setupDeleteFileCache(){
    $upgrade_from = $this->upgrade_from;
    $upgrade_to = $this->upgrade_to;
    if( $upgrade_from !== $upgrade_to ){
      Yii::debug("Deleting file cache ...", __METHOD__);
      $this->emptyDir( __DIR__ . "/../runtime/cache" );
    }
    return "Deleted file cache.";
  }

  /**
   * Check if the file permissions are correct
   *
   * @return mixed
   */
  protected function setupCheckFilePermissions()
  {
    if( ! $this->isNewInstallation ){
      return "Skipping file permissions check.";
    };
    $config_dir = Yii::getAlias('@app/config');
    if (!$this->hasIni and YII_ENV_DEV and !\is_writable($config_dir)) {
      throw new SetupFatalException("The configuration directory needs to be writable in order to create an .ini file: $config_dir");
    } else if (YII_ENV_PROD and \is_writable($config_dir)) {
      return "Warning: The configuration directory must not be writable in production mode";
      //throw new SetupException("The configuration directory must not be writable in production mode: $config_dir");
    } else {
      return 'File permissions ok.';
    }
  }

  /**
   * Check if we have an admin email address
   */
  protected function setupCheckAdminEmail()
  {
    $adminEmail = Yii::$app->config->getIniValue("email.admin");
    if (!$adminEmail) {
      $this->errors[] = "Missing administrator email in app.conf.toml.";
    } else {
      return 'Admininstrator email exists.';
    }
  }

  /**
   * Check if we have a database connection
   *
   * @return array|boolean
   */
  protected function setupCheckDbConnection()
  {
    if (!Yii::$app->db instanceof \yii\db\Connection) {
      throw new SetupFatalException('No database connection.');
    }
    try {
      Yii::$app->db->open();
    } catch (\yii\db\Exception $e) {
      throw new SetupFatalException('Cannot connect to database: ' . $e->errorInfo);
    }
    $this->hasDb = true;
    return  'Database connection ok.';
  }

  /**
   * Run migrations
   * @return string
   * @throws \Exception
   */
  protected function setupDoMigrations()
  {
    $upgrade_from = $this->upgrade_from;
    $upgrade_to = $this->upgrade_to;
    if( $upgrade_from == $upgrade_to ) return "No migration neccessary";

    $expectTables = explode(",",
      "data_Config,data_Datasource,data_Group,data_Messages,data_Permission,data_Role,data_Session,data_User,data_UserConfig," .
      "join_Datasource_Group,join_Datasource_Role,join_Datasource_User,join_Group_User,join_Permission_Role,join_User_Role");
    $allTablesExist = $this->tableExists($expectTables);
    if ($allTablesExist) {
      Yii::debug("All relevant v2 tables exist.", 'migrations');
    } else {
      $missingTables = \array_diff($expectTables, $this->tables());
      if (count(\array_diff($expectTables, $missingTables)) == 0) {
        Yii::debug("None of the relevant v2 tables exist.", 'migrations');
      } else {
        // only some exist, this cannot currently be migrated or repaired
        Yii::error("Cannot upgrade from v2, since the following tables are missing: " . implode(", ", $missingTables));
        throw new SetupFatalException('Invalid database setup. Please contact the adminstrator.');
      }
    }
    // fresh installation
    if ($this->isNewInstallation) {
      $message = 'Found empty database';
    } // if this is an upgrade from a v2 installation, manually add migration history
    elseif ($upgrade_from == "2.x") {
      if (!$allTablesExist) {
        throw new SetupFatalException('Cannot update from Bibliograph v2 data: some tables are missing.');
      }
      Yii::info('Found Bibliograph v2.x data in database. Adding migration history.', 'migrations');
      // set migration history to match the existing data
      try {
        $output = Console::runAction('migrate/mark', ["app\\migrations\\data\\m180105_075933_join_User_RoleDataInsert"]);
      } catch (MigrationException $e) {
        throw new SetupFatalException('Migrating data from Bibliograph v2 failed.');
      }
      if ($output->contains('migration history is set') or
        $output->contains('Nothing needs to be done')) {
        $message = 'Migrated data from Bibliograph v2';
      } else {
        throw new SetupFatalException('Migrating data from Bibliograph v2 failed.');
      }
    } else {
      $message = 'Found data for version $upgrade_from';
    }

    // run new migrations
    try {
      $output = Console::runAction('migrate/new');
    } catch (MigrationException $e) {
      $error = new SetupFatalException("migrate/new failed");
      $error->diagnosticOutput = $e->consoleOutput;
      throw $error;
    }
    if ($output->contains('up-to-date')) {
      Yii::debug('No new migrations.', 'migrations');
      $message = "No updates to the databases.";
    } else {

// @todo
//      if (!$this->isNewInstallation) {
//        // require admin login
//        /** @var \app\models\User $activeUser */
//        $activeUser = Yii::$app->user->identity;
//        // if the current version is >= 3.0.0 and no user is logged in, show a login screen
//        if (version_compare($upgrade_from, "3.0.0", ">=") and (!$activeUser or !$activeUser->hasRole('admin'))) {
//          $message =  "The application needs to be upgraded from '{$oldversion}' to '{$newversion}'. Please log in as administrator.";
//          Login::create($message, "setup", "setup");
//          return [
//            "abort" => "Login required."
//          ];
//        };
//        // admin confirm update
//        if (version_compare($upgrade_from, "3.0.0", ">=") and !$this->migrationConfirmed and !YII_ENV_TEST) {
//          $message = "The database must be upgraded. Confirm that you have made a database backup and now are ready to run the upgrade."; // or face eternal damnation.
//          Confirm::create($message, null, "setup", "setup-confirm-migration");
//          return [
//            "abort" => "Admin needs to confirm the migrations"
//          ];
//        }
//      }


      // run all migrations
      Yii::debug("Applying migrations...", "migrations");
      try {
        $output = Console::runAction("migrate/up");
      } catch (MigrationException $e) {
        $output = $e->consoleOutput;
      }
      if ($output->contains('Migrated up successfully')) {
        Yii::debug("Migrations successfully applied.", "migrations");
        $message .= " and applied new migrations for version {$upgrade_to}";
      } else {
        $error = new SetupFatalException('Initializing database failed.');
        $error->diagnosticOutput = $output;
        throw $error;
      }
    }
    return $message;
  }


  protected function setupPreferences()
  {
    $upgrade_from = $this->upgrade_from;
    $upgrade_to = $this->upgrade_to;
    if( $upgrade_from == $upgrade_to ) return "No config update necessary.";
    $prefs = require Yii::getAlias('@app/config/prefs.php');
    foreach ($prefs as $key => $value) {
      try{
        Yii::$app->config->createKeyIfNotExists(
          $key,
          $value['type'],
          isset($value['customize']) ? isset($value['customize']) :null,
          $value['default'],
          isset($value['final']) ? isset($value['final']) :null
        );
      } catch( \InvalidArgumentException $e ) {
        throw new SetupException("Creating config key '$key' failed.");
      }
    }
    return 'Configuration values created';
  }


  protected function setupSchemas()
  {
    $schemaClass = Yii::$app->config->getPreference('app.datasource.baseclass');
    $schemaExists = false;
    try {
      Schema::register(self::DATASOURCE_DEFAULT_SCHEMA, $schemaClass, [
        'protected' => 1
      ] );
    } catch ( RecordExistsException $e) {
      $schemaExists = true;
    } catch (\ReflectionException $e) {
      throw new SetupException("Invalid schema class '$schemaClass" );
    }
    return $schemaExists ? 'Standard schema existed.' : 'Created standard schema.';
  }


  protected function setupDatasources()
  {
    // only create example databases if this is a new installation
    $datasources = !$this->isNewInstallation ? [] : [
      'datasource1' => [
        'config' => [
          'title' => "Example Database 1",
          'description' => "This database is publically visible"
        ],
        'roles' => ['anonymous', 'user']
      ],
      'datasource2' => [
        'config' => [
          'title' => "Example Database 2",
          'description' => "This database is visible only for logged-in users"
        ],
        'roles' => ['user']
      ]
    ];

    // Import
    $datasources = array_merge( $datasources, [
      'bibliograph_import' => [
        'config' => [
          'title' => "Import",
          'hidden' => 1,
          'description' => "This database is used for importing data"
        ],
        'roles' => ['user']
      ],
    ]);

    $count = 0;
    $found = 0;
    foreach ($datasources as $name => $data) {
      if (\app\models\Datasource::findByNamedId($name)) {
        $found++;
        continue;
      }
      try {
        $datasource = Yii::$app->datasourceManager->create($name);
      } catch (\Exception $e) {
        Yii::error($e);
        throw new SetupException("Could not create datasource '$name':" . $e->getMessage(), null, $e);
      }
      $datasource->setAttributes($data['config']);
      try {
        $datasource->save();
      } catch (Exception $e) {
        Yii::error("Error saving datasource '$name':" . $e->getMessage());
      }
      foreach ((array)$data['roles'] as $roleId) {
        $datasource->link('roles', \app\models\Role::findByNamedId($roleId));
      }
      $count++;
    }

    return $found == $count ? 'Datasources already existed.' : 'Created datasources.';
  }

  protected function setupModules()
  {
    $this->moduleUpgrade = false;
    foreach( Yii::$app->modules as $id => $info){
      /** @var Module $module */
      $module = Yii::$app->getModule($id);
      $msg = null;
      if (! $module instanceof \lib\Module) continue;
      if ($module->disabled === true) {
        $msg = "Module $module->id ($module->name) is disabled.";
      }
      if ($module->version === $module->installedVersion ){
        $msg = "Module $module->id ($module->name) is already at version $module->version.";
      }
      if ($msg){
        Yii::debug($msg, __METHOD__);
        $this->messages[] = $msg;
        continue;
      }
      Yii::debug("Installing module $module->id $module->version", __METHOD__);
      try{
        $enabled = $module->install();
        if( $enabled ){
          $this->messages[] = "Installed module '{$module->id}'.";
          $this->moduleUpgrade = true;
        } else {
          $this->errors = array_merge($this->errors, $module->errors );
        }
      } catch (\Exception $e) {
        Yii::error($e);
        $this->errors[] = "Installing module '{$module->id}' failed: " . $e->getMessage();
      }
    }
    return "Module initialization done";
  }

  /**
   * Migrates datasources
   */
  protected function setupDatasourceMigrations()
  {
    $upgrade_from = $this->upgrade_from;
    $upgrade_to = $this->upgrade_to;
    if( ($this->isNewInstallation or $upgrade_from === $upgrade_to) and ! $this->moduleUpgrade ) {
      $msg = "New installation or no new versions, skipping datasource migration.";
      Yii::debug($msg, __METHOD__);
      return $msg;
    }
    $migrated = [];
    $failed = [];
    /** @var Schema[] $schemas */
    $schemas = Schema::find()->all();
    foreach( $schemas as $schema ){
      try {
        // upgrade old datasources that do not have a 'migration' table yet
        /** @var \app\models\BibliographicDatasource $datasource */
        foreach ($schema->datasources as $datasource) {
          $markerClass = null;
          switch( $schema->namedId ) {
            case "bibliograph_datasource":
            case "bibliograph_extended":
              $markerClass = "M180301071642_Update_table_data_Reference_add_fullext_index";
              break;
          }
          $prefix = $datasource->namedId . "_";
          if( ! $datasource->prefix ){
            $datasource->prefix = $prefix;
            $datasource->save();
          }
          $migration_table_exists = $datasource::getDb()->getTableSchema( $prefix . "migration");
          if( $markerClass and ! $migration_table_exists ){
            Yii::debug("Initializing migrating for datasource table '$datasource->namedId', schema '$schema->namedId'...", __METHOD__);
            $migrationNamespace = Datasource::getInstanceFor($datasource->namedId)->migrationNamespace;
            $fqn = "$migrationNamespace\\$markerClass";
            $params_mark = [
              $fqn,
              'migrationNamespaces' => $migrationNamespace,
            ];
            $db = $datasource->getConnection();
            Yii::debug("Marking datasource '{$datasource->namedId}' with '$fqn'...", __METHOD__);
            Console::runAction('migrate/mark', $params_mark, null, $db);
            $migrated[] = $schema->namedId;
          }
        }
        // run schema migrations
        $count = Yii::$app->datasourceManager->migrate($schema);
        if( $count > 0 ){
          $migrated[]= $schema->namedId;
        }
      } catch (MigrationException $e) {
        $timestamp = time();
        $failedMsg = $schema->namedId . ": " . $e->getMessage() . " ($timestamp)";
        $failed[] = $failedMsg;
        Yii::error( $failedMsg );
        Yii::error((string) $e->consoleOutput);
      }
      // other errors will not be caught
    }

    if (count($failed)) {
      throw new SetupException('Migrating schema(s) failed:' . implode(", ", $failed));
    }
    return count($migrated) ? 'Migrated schema(s) ' . implode(", ", array_unique($migrated))
        : "No schema migrations necessary.";
  }

  /**
   * Check the LDAP connection
   */
  protected function setupLdapConnection()
  {
    $ldap = Yii::$app->ldapAuth->checkConnection();
    $message = $ldap['enabled'] ?'LDAP authentication is enabled' :'LDAP authentication is not enabled.';
    $message .= ($ldap['enabled'] and $ldap['connection']) ? ' and a connection has successfully been established.'
      : $ldap['enabled'] ? ', but trying to establish a connection failed with the error: ' . $ldap['error'] : "";
    if ($ldap['enabled'] and $ldap['error']) {
      throw new SetupException($message);
    }
    return $message;
  }


}
