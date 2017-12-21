<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\controllers\AppController;

use app\models\User;
use app\models\Role;
use app\models\Permission;
use app\models\Group;

use lib\dialog\Alert;
use lib\dialog\Prompt;
use lib\dialog\Confirm;

/*
 * access constants
 */
define( "QCL_ACCESS_READ",    "read"  );
define( "QCL_ACCESS_WRITE",   "write" );
define( "QCL_ACCESS_CREATE",  "create" );
define( "QCL_ACCESS_DELETE",  "delete" );
define( "QCL_ACCESS_ALL",     "*" );

/*
 * three default roles.
 */
define( "QCL_ROLE_ANONYMOUS", "anonymous" );
define( "QCL_ROLE_USER", "user" );
define( "QCL_ROLE_ADMIN", "admin" );

/*
 * the prefix for the anonymous user
 */
if ( ! defined('QCL_ACCESS_ANONYMOUS_USER_PREFIX') )
{
  define('QCL_ACCESS_ANONYMOUS_USER_PREFIX', "anonymous_");
}

/*
 * the timeout of a normal session, in seconds, Defaults to 60 minutes
 */
if ( ! defined('QCL_ACCESS_TIMEOUT') )
{
  define('QCL_ACCESS_TIMEOUT', 60*60 );
}

/*
 * The lifetime of an anonymous user session (if not refreshed), in seconds. Defaults to 1 minute.
 */
if ( ! defined("QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME") )
{
  define( "QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME" , 60 );
}

/*
 * The lifetime of a token (a distributable session id), in seconds. Defaults to 24h
 * todo not yet used, implement
 */
if ( ! defined("QCL_ACCESS_TOKEN_LIFETIME") )
{
  define( "QCL_ACCESS_TOKEN_LIFETIME" , 60*60*24 );
}

/*
 * the salt used for storing encrypted passwords
 */
define('QCL_ACCESS_SALT_LENGTH', 9);


/**
 * The class used for authentication of users. Adds LDAP authentication
 */
class AccessController extends AppController
{
  /**
   * Creates a new anonymous guest user
   * @throws LogicException
   * @return int user id of the new user record
   */
  public function createAnonymous()
  {
      $roleModel =$this->getRoleModel();
      try {
            $roleModel->load("anonymous");
      } catch (qcl_data_model_Exception $e) {
          throw new LogicException("No 'anonymous' role defined.");
      }

      $username = QCL_ACCESS_ANONYMOUS_USER_PREFIX . microtime_float()*100;
      $id = $this->create( $username, array(
      'anonymous' => true,
      'name'      => $this->tr("Anonymous User")
      ) );

    /*
      * link to anonymous role
      */
      try {
          $this->linkModel( $roleModel );
      } catch (qcl_data_model_RecordExistsException $e) {
          $this->warn( $e->getMessage() );
      }
      return $id;
  }
  
  /**
   * Checks if user is anonymous and inactive, and deletes user if so.
   * @see qcl_data_model_AbstractActiveRecord::checkExpiration()
   * @todo Unhardcode expiration time
   */
  protected function checkExpiration()
  {
    $purge = ($this->isAnonymous() && $this->getSecondsSinceLastAction() > 600);
    if ($purge) {
      $this->delete();
    }
    return false;
  }

  /**
   * Checks if the given username has to be authenticated from an LDAP server#
   * @param string $username
   * @throws qcl_access_AuthenticationException if user does not exist
   * @return bool 
   */
  public function isLdapUser($username)
  {
    $userModel = $this->getUserModel();
    try 
    {
      $userModel->load($username);  
    }
    catch ( qcl_data_model_RecordNotFoundException $e)
    {
      throw new qcl_access_AuthenticationException( $this->tr("User '$username' does not exist.") );
    }
    return $userModel->getLdap();
  }

  /**
   * Calling this method with a single argument (the plain text password)
   * will cause a random string to be generated and used for the salt.
   * The resulting string consists of the salt followed by the SHA-1 hash
   * - this is to be stored away in your database. When you're checking a
   * user's login, the situation is slightly different in that you already
   * know the salt you'd like to use. The string stored in your database
   * can be passed to generateHash() as the second argument when generating
   * the hash of a user-supplied password for comparison.
   *
   * See http://phpsec.org/articles/2005/password-hashing.html
   * @param $plainText
   * @param $salt
   * @return string
   */
  public function generateHash( $plainText, $salt = null)
  {
    if ( $salt === null )
    {
      $salt = substr( md5(uniqid(rand(), true) ), 0, QCL_ACCESS_SALT_LENGTH);
    }
    else
    {
      $salt = substr($salt, 0, QCL_ACCESS_SALT_LENGTH );
    }
    return $salt . sha1( $salt . $plainText);
  }

  /**
   * Create a one-time token for authentication. It consists of a random part and the
   * salt stored with the password hashed with this salt, concatenated by "|".
   * @param string $username
   * @return string The nounce
   * @throws qcl_access_AuthenticationException
   */
  public function createNounce( $username )
  {
    $userModel = $this->getUserModel();
    try
    {
      $userModel->load( $username );
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      throw new Exception( $this->tr("Invalid user name or password.") );
    }
    
    $randSalt   = md5(uniqid(rand(), true) );
    $storedSalt = substr( $userModel->getPassword(), 0, QCL_ACCESS_SALT_LENGTH );
    $nounce = $randSalt . "|" . $storedSalt;
    
    // store random salt  and return nounce
    $this->setLoginSalt( $randSalt );
    return $nounce;
  }

  
  private function setLoginSalt( $salt )
  {
    $_SESSION['qcl_access_UserController_createNounce_salt'] = $salt;
  }
  
  private function getLoginSalt()
  {
    return $_SESSION['qcl_access_UserController_createNounce_salt'];
  }
  
  
  
  /**
   * Registers a new user. When exposing this method in a
   * service class, make sure to protect it adequately.
   *
   * @param string $username
   * @param string $password
   * @param array $data
   *    Optional user data
   * @return qcl_access_model_User
   *    The newly created user model instance
   */
  public function actionRegister( $username, $password, $data= array() )
  {
    $userModel = $this->getUserModel();
    $data['password'] = $this->generateHash( $password );
    if( ! $data['name'])
    {
      $data['name'] = $username;
    }
    $userModel->create( $username, $data );
    return $userModel;
  }  

  /**
   * overridden to allow on-the-fly registration
   */
  public function actionAuthenticate( $first=null, $password=null )
  {

    /*
     * do the authentication
     */
    $response = parent::method_authenticate( $first, $password );

    /*
     * check if authentication is allowed at all
     */
    $configModel =  $this->getApplication()->getConfigModel();
    if ( $password and $configModel->getKey("bibliograph.access.mode") == "readonly" )
    {
      if ( ! $this->getActiveUser()->hasRole( QCL_ROLE_ADMIN ) )
      {
        $msg = _("Application is in read-only state. Only the administrator can log in." );
        $explanation = $configModel->getKey("bibliograph.access.no-access-message");
        if ( trim($explanation) )
        {
          $msg .= " " . $explanation;
        }
        throw new qcl_access_AccessDeniedException( $msg );
      }
    }

    /*
     * create dialog that asks user to fill out their user information
     * if the new user is not from LDAP authentication
     */
//    if ( $this->newUser and ! $this->ldapAuth )
//    {
//      new qcl_ui_dialog_Alert(
//        _("Welcome to Bibliograph. After clicking 'OK', please enter your email address and a new password."),
//        "bibliograph.model", "editElement", array( "user", $this->newUser )
//      );
//    }

    /*
     * create dialog that asks user to fill out their user information
     * if the new user is not from LDAP authentication
     */
    if ( strlen($password) == 7 and ! $this->ldapAuth )
    {
      new qcl_ui_dialog_Alert(
        $this->tr("You need to set a new password."),
        "bibliograph.actool", "editElement", array( "user", $first )
      );
    }

    return $response;
  }

  /**
   * Registers a new user.
   * @param string $username
   * @param string $password
   * @param array $data Optional user data
   * @return string
   */
  public function method_register( $username, $password, $data=array() )
  {
    $this->requirePermission("access.manage");
    $accessController = $this->getAccessController();
    $userModel  = $accessController->register( $username, $password, $data );
    $groupModel = $accessController->getGroupModel();
    $groupModel->createIfNotExists("new_users",array('name'=> 'New Users'));
    $groupModel->linkModel($userModel);
    return "OK";
  }

  /**
   * Dialog to export ACL data (ACL Tool)
   * @param $modelType
   * @return qcl_ui_dialog_Confirm
   */
  public function method_exportAccessModelDialog( $modelType )
  {
    $this->requirePermission("access.manage");
    return new qcl_ui_dialog_Confirm(
      sprintf( _( "This will purge all anonymous user data (do this only during maintenance periods) and export all access control data to the backup folder.") , $modelType ),
      null,
      $this->serviceName(), "exportAccessModel", array( $modelType )
    );
  }

  /**
   * Service to export ACL data
   * @param $answer
   * @param $modelType
   * @return qcl_ui_dialog_Alert|string
   */
  public function method_exportAccessModel( $answer, $modelType )
  {
    if ( $answer == false )
    {
      return "ABORTED";
    }
    $this->requirePermission("access.manage");
    qcl_assert_valid_string( $modelType );
    qcl_import("qcl_ui_dialog_Alert");

    $this->exportAccessModels();
    return new qcl_ui_dialog_Alert( _("All data exported to the backup directory.") );
  }

  /**
   * Export ACL data to backup dir
   * @param null $models
   * @return string
   * @throws JsonRpcException
   */
  public function exportAccessModels($models=null)
  {
    $this->requirePermission("access.manage");

    // data will be exported to the backup
    $dir = BACKUP_PATH;
    if ( ! is_writable($dir) )
    {
      throw new JsonRpcException("'$dir' needs to exist and must be writable.");
    }

    qcl_import("qcl_data_model_export_Xml");
    $accessDatasource = $this->getDatasourceModel("access");

    // purge all anonymous users for export
    $this->getAccessController()->purgeAnonymous();

    foreach( $accessDatasource->modelTypes() as $type )
    {
      if ( is_array( $models ) and ! in_array( $type, $models) )
      {
        continue;
      }

      $model = $accessDatasource->getInstanceOfType($type);
      $xml   = $model->export( new qcl_data_model_export_Xml() );
      $file  = $dir . "/" . ucfirst( $type ) . "-" . date("YmdHs") . ".xml";
      file_put_contents( $file, $xml );
      chmod( $file, 0666 );
    }
    return $dir;
  }

   /**
   * Manually cleanup access data
   * @throws LogicException
   */
  public function cleanup()
  {
    $this->log("Cleaning up stale session data ....", QCL_LOG_AUTHENTICATION );

    $sessionModel = $this->getSessionModel();
    $userModel = $this->getUserModel();

    $sessionModel->findAll();
    while ( $sessionModel->loadNext() )
    {
      $sessionId=  $sessionModel->id();

      /*
       * get user that owns the session
       */
      try
      {
        $userModel->findLinked( $sessionModel );
        $userModel->loadNext();
      }
      catch(qcl_data_model_RecordNotFoundException $e)
      {
        /*
         * purge sessions without an associated user
         */
        $this->log("Session $sessionId has no associated user - discarded.", QCL_LOG_AUTHENTICATION  );
        $sessionModel->delete();
        continue;
      }

      /*
       * purge sessions that have expanded their lifetime
       */
      $modified = $sessionModel->getModified();
      $now      = $sessionModel->getQueryBehavior()->getAdapter()->getTime();
      $ageInSeconds = strtotime($now)-strtotime($modified);

      if( $userModel->isAnonymous() )
      {
        //$this->debug("Anonymous session $sessionId is $ageInSeconds seconds old, timeout is " . QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME);
        if ( $ageInSeconds > QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME )
        {
          $this->log("Anonymous Session $sessionId has expired.", QCL_LOG_AUTHENTICATION  );
          $sessionModel->delete();
        }
      }
      else
      {
//        if( $sessionModel->isToken())
//        {
//          if ( $ageInSeconds > QCL_ACCESS_TOKEN_LIFETIME )
//          {
//            $this->log("Token $sessionId has expired.", QCL_LOG_AUTHENTICATION  );
//            $sessionModel->delete();
//           }
//        }
//        else
//        {
        // todo: how to deal with expired user sessions
        //$this->debug(" session $sessionId is $age seconds old, timeout is " . QCL_ACCESS_TIMEOUT);
//        if ( $age > QCL_ACCESS_TIMEOUT )
//        {
//          $userId =$userModel->id();
//          $this->log("Session $sessionId of user $userId unmodified since $age seconds - discarded.", QCL_LOG_AUTHENTICATION  );
//          $sessionModel->delete();
//        }
//        }
      }
    }

    /*
     * Checking for anonymous users without a session
     */
    $userModel->findWhere(array("anonymous"=>true));
    while($userModel->loadNext())
    {
      try
      {
        $sessionModel->findLinked($userModel); // will throw when no session is found
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        $userId= $userModel->id();
        $this->log("Anonymous user #$userId has no sessions - discarded.", QCL_LOG_AUTHENTICATION  );
        $userModel->delete();
      }
    }

    /*
     * Checking for sessions without a user
     */
    $sessionModel->findAll();
    while($sessionModel->loadNext())
    {
      try
      {
        $userModel->findLinked($sessionModel); // will throw when no session
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        $sessionId= $sessionModel->id();
        $this->log("Session $sessionId has no associated user - discarded.", QCL_LOG_AUTHENTICATION  );
        $sessionModel->delete();
      }
    }


    /*
     * cleanup messages
     */
    $this->getMessageBus()->cleanup();
  }

    /**
   * Checks if any session exists that are connected to the user id. 
   * If not, set the user's online status to false.
   * @param integer $userId
   * @return boolean Whether the user is online or not.
   * @throws LogicException if user does not exist.
   */
  public function checkOnlineStatus( $userId )
  {
    try
    {
      $userModel = $this->getUserModel()->load( $userId );
    }
    catch(qcl_data_model_RecordNotFoundException $e)
    {
      throw new LogicException( "User #$userId does not exist." );
    }

    try
    {
      $this->getSessionModel()->findLinked($userModel);
      return true;
    }
    catch(qcl_data_model_RecordNotFoundException $e)
    {
       $userModel->set("online", false)->save();
       return false;
    }
  }

  /**
   * Returns number of seconds since resetLastAction() has been called
   * for the current user
   * @return int seconds
   */
  public function getSecondsSinceLastAction()
  {
    not_implemented();
    $now = new qcl_data_db_Timestamp();
    $lastAction = $this->get("lastAction");
    if ($lastAction) {
      $d = $now->diff($lastAction);
      return (int) ($d->s + (60 * $d->i) + (3600 * $d->h) + 3600 * 24 * $d->d);
    }
    return 0;
  }

  /**
   * Purges all anonymous users. This will interfere with the sessions of these users,
   * therefore use this only during maintenance
   */
  public function purgeAnonymous()
  {
    $userModel = $this->getUserModel();
    $userModel->findAll();
    while( $userModel->loadNext() )
    {
      if( $userModel->isAnonymous() )
      {
        $userModel->delete();
      }
    }
  }

  /**
   * Function to check the match between the password and the repeated
   * password. Returns the hashed password.
   * @param $value
   * @throws JsonRpcException
   * @return string|null
   */
  public function checkFormPassword($value)
  {
    if (!isset($this->__password)) {
      $this->__password = $value;
    } elseif ($this->__password != $value) {
      throw new JsonRpcException($this->tr("Passwords do not match..."));
    }
    if ($value and strlen($value) < 8) {
      throw new JsonRpcException($this->tr("Password must be at least 8 characters long"));
    }
    return $value ? $this->getApplication()->getAccessController()->generateHash($value) : null;
  }

  //----------------------------------------------------------------
  // convenience methods  access control
  //----------------------------------------------------------------  

  /**
   * Returns true if a permission with the given named id exists and false if
   * not. 
   * @param string $namedId The named id of the permission
   * @return bool
   */
  public function hasPermission( $namedId )
  {
    return $this->getAccessController()->getPermissionModel()->namedIdExists($namedId);
  }

  /**
   * Creates a permission with the given named id if it doesn't
   * already exist. 
   * @param array|string $namedId The named id(s) of the permission(s)
   * @param string $description Optional description of the permission. 
   *    Only used when first argument is a string.
   * @return void
   */
  public function addPermission( $namedId, $description=null )
  {
    if ( is_array($namedId) )
    {
      foreach( $namedId as $id )
      {
        $this->addPermission( $id );
      }
      return;
    }
    $this->getAccessController()->getPermissionModel()
      ->createIfNotExists($namedId, array( "description" => $description ));
  }

  /**
   * Removes a permission with the given named id. Silently fails if the 
   * permission doesn't exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @return void
   */
  public function removePermission( $namedId )
  {
    if ( is_array($namedId) )
    {
      foreach( $namedId as $id )
      {
        $this->removePermission( $id );
      }
      return;
    }    
    try
    {
      $this->getAccessController()->getPermissionModel()->load($namedId)->delete();  
    }
    catch( qcl_data_model_RecordNotFoundException $e){}
  }
  
  /**
   * Assign the given role the given permissions
   * @param string $roleId The named id of the role
   * @param string|array $permissions The named id(s) of the permissions
   * @throws LogicException 
   */
  public function giveRolePermission( $roleId, $permissions )
  {
    try
    {
      $roleModel = $this->getAccessController()->getRoleModel()->load( $roleId );  
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      throw new LogicException("Unknown role '$roleId'");
    }
    $permissionModel = $this->getAccessController()->getPermissionModel();
    foreach( (array) $permissions as $permissionId )
    {
      try
      {
        $roleModel->linkModel( $permissionModel->load( $permissionId ) );   
      }
      catch( qcl_data_model_RecordExistsException $e ) {}
      catch( qcl_data_model_RecordNotFoundException $e )
      {
        throw new LogicException("Unknown permission '$permissionId'");
      }
    }
  }  

  /**
   * Deletes a user
   */
  public function deleteUser()
  {
    $this->dispatchMessage("user.deleted", $this->id());
    parent::delete();
  }

}
