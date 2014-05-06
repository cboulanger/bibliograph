<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("qcl_access_Service");


/**
 * The class used for authentication of users. Adds LDAP authentication
 */
class bibliograph_service_Access
  extends qcl_access_Service
{

  /**
   * overridden to allow on-the-fly registration
   */
  public function method_authenticate( $first=null, $password=null )
  {
    /*
     * on-the-fly registration and authentication with the
     * self-register- password
     * @todo replace with a more sophisticated system
     */
    if ( defined("BIBLIOGRAPH_SELF_REGISTER_PASSWORD") )
    {
      if ( $password == BIBLIOGRAPH_SELF_REGISTER_PASSWORD )
      {
        $username = trim( $first );

        try
        {
          qcl_assert_valid_string( $username );
          qcl_assert_regexp( "/[a-zA-Z0-9\.\-\_]+/", $username );
        }
        catch( InvalidArgumentException $e )
        {
          throw new JsonRpcException( "Invalid user name '$username'. Name must only contain letters, numbers or '.-_'." );
        }
        $userModel = $this->getAccessController()->getUserModel();

        if ( $userModel->namedIdExists( $username ) )
        {
          throw new JsonRpcException( "User '$username' exists." );
        }

        $userModel->create($username,array(
          'name'      => $username,
          'password'  => $password
        ) );

        /*
         * link with user role
         */
        $roleModel = $this->getAccessController()->getRoleModel();
        $roleModel->load( BIBLIOGRAPH_ROLE_USER );
        $userModel->linkModel( $roleModel );

        $this->newUser = $username;
        $first = $username;
      }
    }

    /*
     * do the authentication
     */
    $response = parent::method_authenticate( $first, $password );

    /*
     * check if authentication is allowed at all
     * todo: is this necessary at all? We have application modes
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
    if ( $this->newUser and ! $this->ldapAuth )
    {
      /*
       * alert
       */
      qcl_import("qcl_ui_dialog_Alert");
      new qcl_ui_dialog_Alert(
        _("Welcome to Bibliograph. After clicking 'OK', please enter your email address and a new password."),
        "bibliograph.model", "editElement", array( "user", $this->newUser )
      );
    }
    return $response;
  }

  /**
   * Registers a new user.
   * @param string $username
   * @param string $password
   * @param array $data Optional user data
   * @return unknown_type
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
   * @param $modelType
   * @return qcl_ui_dialog_Confirm
   */
  public function method_exportAccessModelDialog( $modelType )
  {
    $this->requirePermission("access.manage");
    qcl_import("qcl_ui_dialog_Confirm");

    return new qcl_ui_dialog_Confirm(
      sprintf( _( "This will purge all anonymous user data (do this only during maintenance periods) and export all access control data to the backup folder.") , $modelType ),
      null,
      $this->serviceName(), "exportAccessModel", array( $modelType )
    );
  }

  /**
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
   * @param null $models
   * @return string
   * @throws JsonRpcException
   */
  public function exportAccessModels($models=null)
  {
    $this->requirePermission("access.manage");

    // data will be exported to the backup
    $dir = BIBLIOGRAPH_BACKUP_PATH;
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
   * Service to collect events and messages waiting for a particular connected session.
   * Returns the number of milliseconds after which to poll again.
   * @return int
   */
  public function method_getMessages()
  {
    /*
     * cleanup stale sessions
     */
    $this->getAccessController()->cleanup();

    /*
     * determine the polling frequency based on the number of connected users
     */
    $sessionModel = $this->getAccessController()->getSessionModel();
    $numberOfSessions = $sessionModel->countRecords();
    $pollingFrequencyInMs = QCL_EVENT_MESSAGE_POLLING_INTERVAL + (QCL_EVENT_MESSAGE_POLLING_DELAYPERSESSION*($numberOfSessions-1));

    return $pollingFrequencyInMs;
  }

  /*
  public function method_reloadAccessModelDialog( $modelType )
  {
    qcl_import("qcl_ui_dialog_Alert");
    return new qcl_ui_dialog_Alert( _("Feature currently deactivated.") );


    $this->requirePermission("access.manage");
    qcl_import("qcl_ui_dialog_Confirm");
    qcl_assert_valid_string( $modelType );
    return new qcl_ui_dialog_Confirm(
      sprintf( _( "Do you really want to reset/upgrade the '%s' data?") , $modelType ),
      null,
      $this->serviceName(), "reloadAccessModel", array( $modelType )
    );
  }

  public function method_reloadAccessModel( $answer, $modelType )
  {
    if ( $answer == false )
    {
      return "ABORTED";
    }
    $this->requirePermission("access.manage");
    qcl_assert_valid_string( $modelType );
    qcl_import("qcl_ui_dialog_Alert");

    $app = $this->getApplication();
    $map = $app->getInitialDataMap();
    if ( ! isset( $map[$modelType] ) )
    {
      throw new JsonRpcException("No data exists for '$modelType'");
    }
    $app->importInitialData(array(
      $modelType => $map[$modelType]
    ) );
    $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    return new qcl_ui_dialog_Alert( sprintf( _("'%s' data reset."), $modelType ) );
  }


  */



}
?>