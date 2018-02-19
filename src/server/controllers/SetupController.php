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

use Yii;
use app\models\User;
//use lib\dialog\RemoteWizard;
use lib\dialog\Login;
use lib\dialog\Confirm;
use lib\components\ConsoleAppHelper as Console;
use Stringy\Stringy;

/**
 * Setup controller. Needs to be the first controller called 
 * by the application after loading
 */
class SetupController extends \app\controllers\AppController
{

  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["setup","version","setup-version"];

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
   * The properties of the progress dialog to show on the client
   * @var array
   */
  protected $progressDialogProperties = [
    'showLog' => true,
    'hideWhenCompleted' => false,
    'okButtonText' => "OK"
  ];


  //-------------------------------------------------------------
  // ACTIONS
  //-------------------------------------------------------------  

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
   * The setup action. Is called as first server method from the client 
   */
  public function actionSetup(){
    return $this->_setup();
  }

  /**
   * A setup a specific version of the application. This is mainly for testing. 
   * @param string $upgrade_to(optional) The version to upgrade from. 
   * @param string $upgrade_from (optional) The version to upgrade to.
   */
  public function actionSetupVersion($upgrade_to, $upgrade_from=null){
    if ( ! YII_ENV_TEST ){
      throw \BadMethodCallException('setup/setup-version can only be called in test mode.');
    }
    return $this->_setup($upgrade_to, $upgrade_from);
  }  

  /**
   * The setup action. Is called as first server method from the client 
   * @param string $upgrade_to (optional) The version to upgrade to. You should not set
   * this parameter unless you know what you are doing. 
   *  @param string $upgrade_from (optional) The version to upgrade from.You should not set
   * this parameter unless you know what you are doing. 
   * @return array
   */
  protected function _setup($upgrade_to=null, $upgrade_from=null)
  {
    if ( ! $upgrade_from ) {
      try {
        $upgrade_from = Yii::$app->config->getKey('app.version');
      } catch( \InvalidArgumentException $e ) {
        // upgrading from version 2 where the config key doesn't exist
        $upgrade_from = "2.x";
      } catch( \yii\db\Exception $e ) {
        // no tables exist yet, this is the first run of a fresh installation
        $upgrade_from = "0.0.0";        
      } catch ( yii\base\InvalidConfigException $e ){
        // this happens deleting the tables in the database during development
        // @todo 
        $upgrade_from = "0.0.0";  
        session_destroy();
      } 
    }
    if ( ! $upgrade_to ){
      $upgrade_to = Yii::$app->utils->version;
    }
    // @todo validate
    
    // if application version has changed or first run, run setup methods
    if( $upgrade_to != $upgrade_from ){
      // visual marker in log file
      Yii::trace(
        "\n\n\n" .str_repeat("*",80) . "\n\n BIBLIOGRAPH SETUP\n\n" . str_repeat("*",80) . "\n\n\n",
        'marker'
      );      
      Yii::info("Application version has changed from '$upgrade_from' to '$upgrade_to', running setup methods");
      
      // run methods. If any of them returns a fatal error, abort and alert the user
      // if any non-fatal errors occur, collect them and display them to the user at the 
      // end, then consider setup unsuccessful
      $success = $this->runSetupMethods($upgrade_from, $upgrade_to);
      if ( $success ){
        $upgrade_from = $upgrade_to;
        Yii::info("Setup of version '$upgrade_from' finished successfully.");
        if( ! Yii::$app->config->keyExists('app.version') ){
          // createKey( $key, $type, $customize=false, $default=null, $final=false )
          Yii::$app->config->createKey( 'app.version','string',false,null,false) ;  
        } 
        Yii::$app->config->setKeyDefault( 'app.version', $upgrade_from );  
      } else {
        Yii::info(
          "Setup of version '$upgrade_from' failed with the following errors:" .
          implode( "\n", $this->errors)
        );
        return null;      
      }
    }

    // notify client that setup it done
    $this->dispatchClientMessage("ldap.enabled", Yii::$app->config->getIniValue("ldap.enabled") );
    $this->dispatchClientMessage("bibliograph.setup.done"); // @todo rename

    // return errors and messages
    return [
      'errors' => $this->errors,
      'messages' => $this->messages
    ];
  }  

  /**
   * Run all existing setup methods, i.e. methods of this class that have the prefix 'setup'. 
   * Each method must be executable regardless of application setup state and will be called
   * with the same parameters as this method. The must return one of these value types:
   * 
   * - false : do nothing
   * - [ 'fatalError'] => "Fatal error message" ] This will end the setup process and alert a 
   *   message to the user
   * - [ 'error' => "Error message", 'message' => "Result message' ] This will let the setup 
   *   process continue. Messages are stored in the 'messages' member property, errors in the
   *   'error' member property of this object 
   * 
   * @param string $upgrade_from 
   *    The current version of the application as stored in the database
   * @param string $upgrade_to The version in package.json, i.e. of the code, which can be
   *    higher than the 
   * @return void
   */
  protected function runSetupMethods($upgrade_from, $upgrade_to)
  {
    // compile list of setup methods
    foreach( \get_class_methods( $this ) as $method ){
      if( Stringy::create( $method )->startsWith("setup") ){
        $result = $this->$method($upgrade_from, $upgrade_to);
        if( ! $result ) continue;
        $fatalError = isset($result['fatalError']) ? $result['fatalError'] : false; 
        if ( $fatalError ){
          Yii::error($fatalError);
          \lib\dialog\Error::create( $fatalError );
          return false;
        }
        if( isset($result['error'])){
          $this->errors[] = $result['error'];
        }
        if( isset($result['message'])){
          $this->messages[] = $result['message'];
        }
        if( count($this->errors) ){
          \array_unshift($this->errors, Yii::t('app','<b>Setup failed. Please fix the following problems:</b>'));
          \lib\dialog\Error::create( \implode('<br>',$this->errors));
          return false;
        }
      }
    }
    // Everything seems to be ok
    return true;
  }

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
    if( is_null($tables) ){
      $dbConnect = \Yii:: $app->get('db');
      if (!($dbConnect instanceof \yii\db\Connection)){
        throw new \yii\db\Exception('Cannot establish db connection');
      }
      $tables = $dbConnect->schema->getTableNames();
      if( ! count($tables) ){
        Yii::trace("Database {$dbConnect->dsn} does not contain any tables." . implode(", ", $tables));
        return [];
      }
      Yii::trace("Tables in the database {$dbConnect->dsn}: " . implode(", ", $tables));
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
    if( is_array( $tableName ) ){
      return count( \array_diff( $tableName, $tables ) ) == 0;
    }
    return \in_array($tableName, $tables);
  }

  //-------------------------------------------------------------
  // CHECK METHODS
  //-------------------------------------------------------------  

  /**
   * Check if an ini file exists
   *
   * @return void
   */
  protected function setupCheckIniFileExists()
  {
    $this->hasIni = file_exists(Yii::getAlias('@app/config/bibliograph.ini.php'));
    if( ! $this->hasIni ){
      if ( YII_ENV_PROD ) {
        return [
          'fatalError' => Yii::t('app','Cannot run in production mode without ini file.')
        ];
      } else {
        return [
          'fatalError' => "Wizard not implemented yet. Please add ini file as per installation instructions."
        ];        
      }
    }
    //OK
    return [
      'message' => Yii::t('app','Ini file exists.')
    ];
  }

  /**
   * Check if the file permissions are correct
   *
   * @return void
   */
  protected function setupCheckFilePermissions()
  {
    $config_dir = Yii::getAlias('@app/config');
    if ( ! $this->hasIni and YII_ENV_DEV and ! \is_writable($config_dir) ) {
      return [
        'error' => Yii::t('app',"The configuration directory needs to be writable in order to create an .ini file: {config_dir}.",[
          'config_dir' => $config_dir
        ])
      ];
    }
    if ( YII_ENV_PROD and \is_writable($config_dir) ) {
      return [
        'error' => Yii::t('app',"The configuration directory must not be writable in production mode {config_dir}.",[
          'config_dir' => $config_dir
        ])
      ];
    }

    // OK
    return [
      'message' => 'File permissions ok.'
    ];
  }

  /**
   * Check if we have an admin email address
   *
   * @return void
   */
  protected function setupCheckAdminEmail()
  {
    $adminEmail = Yii::$app->config->getIniValue("email.admin");
    if ( ! $adminEmail ){
      return [
        'error' => Yii::t('app',"Missing administrator email in bibliograph.ini.php." )
      ];
    }
    return [
      'message' => Yii::t('app','Admininstrator email exists.')
    ];
  }  

  /**
   * Check if we have a database connection
   *
   * @return void
   */
  public function setupCheckDbConnection()
  {
    if( ! Yii::$app->db instanceof \yii\db\Connection ){
      return [
        'fatalError' => Yii::t('app','No database connection. ')
      ];
    }
    try {
      Yii::$app->db->open();
    } catch( \yii\db\Exception $e) {
      return [
        'fatalError' => Yii::t('app','Cannot connect to database: {error} ',[
          'error' => $e->errorInfo
        ])
      ];      
    }
    $this->hasDb = true;
    return [
      'message' => 'Database connection ok.'
    ];    
  }  

  /**
   * Run migrations
   * @return array
   */
  protected function setupDoMigrations($upgrade_from, $upgrade_to)
  {
    $expectTables = explode(",",
        "data_Config,data_Datasource,data_Group,data_Messages,data_Permission,data_Role,data_Session,data_User,data_UserConfig," .
        "join_Datasource_Group,join_Datasource_Role,join_Datasource_User,join_Group_User,join_Permission_Role,join_User_Role");
    $allTablesExist = $this->tableExists($expectTables);
    if ( $allTablesExist ){
      Yii::trace( "All relevant v2 tables exist.", 'migrations' );
    } else {
      $missingTables = \array_diff( $expectTables, $this->tables() );
      if( count(\array_diff( $expectTables, $missingTables ) ) ==0 ){
        Yii::trace( "None of the relevant v2 tables exist.", 'migrations' );
      } else {
        // only some exist, this cannot currently be migrated or repaired
        return [
          'fatalError' => Yii::t('app','Invalid database setup. Please contact the adminstrator.')
        ];
      }
    }
    // fresh installation
    if( $upgrade_from == "0.0.0"  ){
      $message = Yii::t('app','Found empty database');
    }
    // if this is an upgrade from a v2 installation, manually add migration history
    elseif ( $upgrade_from == "2.x" ) {
      if( ! $allTablesExist ){
        return [
          'fatalError' => Yii::t('app','Cannot update from Bibliograph v2 data: some tables are missing.')
        ];
      }
      Yii::info('Found Bibliograph v2.x data in database. Adding migration history.','migrations');
      // set migration history to match the existing data
      $output = Console::runAction('migrate/mark', ["app\\migrations\\data\\m180105_075933_join_User_RoleDataInsert"]);
      if ( $output->contains('migration history is set') or 
          $output->contains('Nothing needs to be done') ){
        $message = Yii::t('app','Migrated data from Bibliograph v2');
      } else {
        return [
          'fatalError' => Yii::t('app','Migrating data from Bibliograph v2 failed.')
        ];
      }       
    } else {
      $message = Yii::t('app','Found data for version {version}',[
        'version' => $upgrade_from
      ]);
    }
    // run new migrations
    $output = Console::runAction('migrate/new');
    if( $output->contains('up-to-date') ){
      Yii::info('No new migrations.','migrations');
      $message = Yii::t('app',"No updates to the databases.");
    } else {
      // unless this is a fresh installation, require admin login
      $activeUser = Yii::$app->user->identity;
      // if the current version is >= 3.0.0 and no user is logged in, show a login screen
      if( version_compare( $upgrade_from, "3.0.0", ">=" ) and ( ! $activeUser or  ! $activeUser->hasRole('admin') ) ){
        $message = Yii::t('app',"The application database needs to be upgraded from '{oldversion}' to '{newversion}'. Please log in as administrator.", [
          'oldversion' => $upgrade_from,
          'newversion' => $upgrade_to
        ]);
        Login::create( $message, "setup", "setup" );
        return "Login required.";
      };
      // unless we're in test mode, let the admin confirm 
      if( version_compare( $upgrade_from, "3.0.0", ">=" )  and ! $this->migrationConfirmed and ! YII_ENV_TEST){
        $message = Yii::t('app',"The database must be upgraded. Confirm that you have made a database backup and now are ready to run the upgrade."); // or face eternal damnation.
        Confirm::create($message,null,"setup","setup-confirm-migration");
        return "User needs to confirm the migrations";
      }
      // run all migrations 
      Yii::trace("Applying migrations...","migrations");
      $output = Console::runAction("migrate/up");
      // @todo check if migration was successful
      if ( $output->contains('Migrated up successfully') ){
        Yii::trace("Migrations successfully applied.","migrations");
        $message .= Yii::t('app', ' and applied new migrations for version {version}',[
          'version' => $upgrade_to
        ]);
      } else {
        return [
          'fatalError' => Yii::t('app', 'Initializing database failed.')
        ];
      }
    } 
    return [
      'message' => $message
    ];    
  }

  /**
   * Check the LDAP connection
   *
   * @return array
   */
  protected function setupCheckLdapConnection()
  {
    $ldap = Yii::$app->ldapAuth->checkConnection();
    $message = $ldap['enabled'] ? 
      Yii::t('app', 'LDAP authentication is enabled') :
      Yii::t('app', 'LDAP authentication is not enabled.');
    $message .=  (  $ldap['enabled'] and $ldap['connection'] ) ? 
      Yii::t('app', ' and a connection has successfully been established.') :
      $ldap['enabled'] ?  Yii::t('app', ', but trying to establish a connection failed with the error: {error}', [
        'error' => $ldap['error']
      ]) : "";
    if ( $ldap['enabled'] and $ldap['error'] ){
      $result = [ 'error' => $message ];
    } else {
      $result = [ 'message' => $message ];
    }
    return $result;
  }

  /**
   * Setup two example datasources
   *
   * @return void
   */
  protected function setupExampleDatasources()
  {
    $datasources = [
      'datasource1' => [
        'config' => [
          'title'       => "Example Database 1",
          'description' => "This database is publically visible"
        ],
        'roles' => ['anonymous','user']
      ],
      'datasource2' => [
          'config' => [
            'title'       => "Example Database 2",
            'description' => "This database is visible only for logged-in users"                
          ],
          'roles' => ['user']
      ],
    ];

    $count = 0; $found = 0; 
    foreach( $datasources as $name => $data){
      if( \app\models\Datasource::findByNamedId($name) ){
        $found++; continue;
      }
      $datasource = Yii::$app->datasourceManager->create( $name );
      $datasource->setAttributes( $data['config'] );
      $datasource->save();
      foreach( $data['roles'] as $roleId ){
        $datasource->link( 'roles', \app\models\Role::findByNamedId($roleId) );
      }
      $count++;
    }

    return [
      'message' => $found == $count ?
        Yii::t('app','Example databases already existed.') :
        Yii::t('app','Example databases were created.')
    ];
  }
}