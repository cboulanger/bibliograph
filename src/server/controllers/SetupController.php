<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2015 Christian Boulanger

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
use lib\dialog\RemoteWizard;
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
  protected $noAuthActions = ["setup"];


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
   * The entry method. If the application is already setup, do nothing. Otherwise,
   * display progress dialog on the client and start setup service
   */
  public function actionSetup()
  {
    if( ! Yii::$app->config->keyExists('bibliograph.setup') ){
      $success = $this->runSetupMethods();
      if ( $success ){
        // createKey( $key, $type, $customize=false, $default=null, $final=false )
        Yii::$app->config->createKey('bibliograph.setup','boolean',false,true,true);  
      } else {
        // setup failed
        return null;      
      }
    } 
    // notify client that setup it done
    $this->dispatchClientMessage("ldap.enabled", Yii::$app->config->getIniValue("ldap.enabled") );
    $this->dispatchClientMessage("bibliograph.setup.done");

    // return errors and messages
    return [
      'errors' => $this->errors,
      'messages' => $this->messages
    ];
  }  

  protected function runSetupMethods()
  {
    // compile list of setup methods
    foreach( \get_class_methods( $this ) as $method ){
      if( Stringy::create( $method )->startsWith("setup") ){
        $result = $this->$method();
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
   * @param $tableName
   * @return bool table exists in schema
   * @throws \yii\base\InvalidParamException
   */
  private function tableExists($tableName)
  {
    $dbConnect = \Yii::$app->get('db');
    if (!($dbConnect instanceof \yii\db\Connection)){
      throw new \yii\base\InvalidParamException;
    }
    $tables = $dbConnect->schema->getTableNames();
    Yii::trace("Tables in the database {$dbConnect->dsn}: " . implode(", ", $tables));
    return in_array($tableName, $tables);
  }

  //-------------------------------------------------------------
  // CHECK METHODS
  //-------------------------------------------------------------  

  protected function setupCheckIniFileExists()
  {
    $this->hasIni = file_exists(Yii::getAlias('@app/config/bibliograph.ini.php'));
    if( ! $this->hasIni and YII_ENV_PROD ){
      return [
        'fatalError' => Yii::t('app','Cannot run in production mode without ini file.')
      ];
    }
    //OK
    return [
      'message' => Yii::t('app','Ini file exists.')
    ];
  }

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
   * Run migrations if neccessary
   *
   * @return array
   */
  protected function setupDoMigrations()
  {
    // visual marker in log file
    Yii::trace(
      "\n\n\n" .
      str_repeat("*",80) . "\n\n" .
      " BIBLIOGRAPH SETUP\n\n" .
      str_repeat("*",80) . 
      "\n\n\n",
      'marker'
    );

    if( YII_ENV_PROD ){
      Yii::trace('Skipping migrations in production mode...');
      return false;
    };
    $output = Console::runAction('migrate/new');
    if( $output->contains('up-to-date') ){
      Yii::info('No new migrations.','migrations');
      $message = "No database migrations necessary";
    } else {
      // upgrade from v2?
      if( $this->tableExists("data_User")  ){
        Yii::info('Found Bibliograph data in database. Adding migration history.','migrations');
        // set migration history to match the existing data
        $output = Console::runAction('migrate/mark', ["app\\migrations\\data\\m180105_075933_join_User_RoleDataInsert"]);
        if ( $output->contains('migration history is set') ){
          $message = Yii::t('app','Migrated data from Bibliograph v2');
        } else {
          return [
            'fatalError' => Yii::t('app','Migrating data from Bibliograph v2 failed.')
          ];
        }
      } else {
        // no, this is a fresh installation
        $message = Yii::t('app','Found empty database');
        Yii::trace("No existing data.","migrations");
      }
      // run all migrations 
      Yii::trace("Applying migrations...","migrations");
      $output = Console::runAction("migrate/up");
      // @todo check if migration was successful
      if ( $output->contains('Migrated up successfully') ){
        Yii::trace("Migrations successfully applied.","migrations");
        $message .= Yii::t('app', ' and applied new migrations.');
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

  protected function setupCheckLdapConnection()
  {
    return false;
  }

  protected function setupCheckAdminEmail()
  {
    $adminEmail = Yii::$app->config->getIniValue("email.admin");
    if ( ! $adminEmail ){
      return [
        'error' => Yii::t('app',"Missing administrator email in bibliograph.ini.php." )
      ];
    }
    return [
      'message' => Yii::t('app','Admininstrator email exists')
    ];
  }
}