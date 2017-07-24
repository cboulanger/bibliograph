<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */
qcl_import("qcl_server_Service");
qcl_import("qcl_server_Response");
qcl_import("qcl_data_Result");
qcl_import( "qcl_data_datasource_Manager" );

/**
 * Common base class for controllers. Mainly contains convenience
 * methods that proxy methods originating in other (manager) objects.
 */
class qcl_data_controller_Controller
  extends qcl_server_Service
{

  //-------------------------------------------------------------
  // class properties
  //-------------------------------------------------------------

  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $acl = array();

  /**
   * The types of access control lists this controller maintains
   * Defaults to "model"
   * @var array
   */
  protected $aclTypes = array( "model", "record" );

  /**
   * Whether datasource access should be restricted according
   * to the current user. The implementation of this behavior is
   * done by the getAccessibleDatasources() and checkDatasourceAccess()
   * methods.
   *
   * @var bool
   */
  protected $controlDatasourceAccess = false;

  //-------------------------------------------------------------
  // initialization
  //-------------------------------------------------------------


  //-------------------------------------------------------------
  // access control on the session level
  //-------------------------------------------------------------

  /**
   * Shorthand getter for access controller
   * @return qcl_access_SessionController
   */
  public function getAccessController()
  {
    return $this->getApplication()->getAccessController();
  }

  /**
   * Shorthand getter for active user object
   * @return qcl_access_model_User
   */
  public function getActiveUser()
  {
    return $this->getAccessController()->getActiveUser();
  }

  /**
   * Shorthand getter for  the current session id.
   * @return string
   */
  public function getSessionId()
  {
    return $this->getAccessController()->getSessionId();
  }

  /**
   * Checks if active user has the given permission.
   * @param $permission
   * @return bool
   */
  public function hasPermission( $permission )
  {
    return $this->getActiveUser()->hasPermission( $permission );
  }

  /**
   * Checks if active user has the given permission and aborts if
   * permission is not granted.
   *
   * @param string $permission
   * @return bool
   * @throws qcl_access_AccessDeniedException
   */
  public function requirePermission( $permission )
  {
    if ( !  $this->hasPermission( $permission ) )
    {
      $this->warn( sprintf(
        "Active user %s does not have required permission %s",
        $this->getActiveUser(), $permission
      ) );
      throw new qcl_access_AccessDeniedException("Access denied.");
    }
  }

  /**
   * Shorthand method to check if active user has a role
   * @param string $role
   * @return bool
   */
  public function hasRole( $role )
  {
    return $this->getActiveUser()->hasRole( $role );
  }

  /**
   * Shorthand method to enforce if active user has a role
   * @param string $role
   * @throws qcl_access_AccessDeniedException
   * @return bool
   */
  public function requireRole( $role )
  {
    if ( !  $this->hasRole( $role ) )
    {
      $this->warn( sprintf(
        "Active user %s does hat required role %s",
        $this->getActiveUser(), $role
      ) );
      throw new qcl_access_AccessDeniedException("Access denied.");
    }
  }

  //-------------------------------------------------------------
  // access control on the datasource-level
  //-------------------------------------------------------------

  /**
   * Returns a list of datasources that is accessible to the current user.
   * Accessibility is restricted by the group-datasource, the role-datasource
   * relation and the user-datasource relation.
   *
   * @return array
   */
  public function getAccessibleDatasources()
  {
    static $datasources = null;

    if ( $datasources === null )
    {

      $datasources  = array();
      $activeUser   = $this->getAccessController()->getActiveUser();
      $roleModel    = $this->getAccessController()->getRoleModel();
      $groupModel   = $this->getAccessController()->getGroupModel();
      $dsModel      = $this->getDatasourceModel();
      $activeUserName  = $activeUser->namedId();

      /*
       * find all groups that the current user belongs to
       */
      $groups = array();
      try
      {
        $groupModel->findLinked( $activeUser );
        /*
         * find all datasources this groups have access to
         */
        while( $groupModel->loadNext() )
        {
          $groupName = $groupModel->namedId();
          $groups[] = $groupName;
          try
          {
            $dsModel->findLinked( $groupModel );
            while( $dsModel->loadNext() )
            {
              $datasources[] = $dsModel->namedId();
            }
          }
          catch( qcl_data_model_RecordNotFoundException $e )
          {
            $this->log("Group '$groupName' has no access to a datasource.", QCL_LOG_ACL);
          }
        }
        $this->log(sprintf(
          "Membership in groups '%s' provided access to the datasources '%s'",
          implode(", ", $groups), implode(", ",$datasources)
        ), QCL_LOG_ACL);
      }
      catch( qcl_data_model_RecordNotFoundException $e )
      {
        $this->log("Active user '$activeUserName' does not belong to any groups.",QCL_LOG_ACL);
      }

      /*
       * find all datasources that are linked to a (global) role
       */
      $roles = array();
      $roleDatasources = array();
      try
      {
        $query = $roleModel->findLinkedNotDepends( $activeUser, $groupModel );
        while( $roleModel->loadNext($query) ) // necessary because internal query is modified by inner loop
        {
          $roleName = $roleModel->namedId();
          $roles[] = $roleName;
          try
          {
            $dsModel->findLinked( $roleModel );
            while( $dsModel->loadNext() )
            {
              $datasources[] = $dsModel->namedId();
              $roleDatasources[] = $dsModel->namedId();
            }
          }
          catch( qcl_data_model_RecordNotFoundException $e )
          {
            $this->log("Role '$roleName' has no access to a datasource.", QCL_LOG_ACL);
          }
        }
        $this->log(sprintf(
          "Membership in roles '%s' provided access to the datasources '%s'",
          implode(", ", $roles), implode(", ",$roleDatasources)
        ), QCL_LOG_ACL);
      }
      catch( qcl_data_model_RecordNotFoundException $e )
      {
        $this->log("Active user has no global role.",QCL_LOG_ACL);
      }

      /*
       * find all datasources that are linked to the user
       */
      $userDatasources = array();
      try
      {
        $dsModel->findLinked( $activeUser );
        while( $dsModel->loadNext() )
        {
          $datasources[]     = $dsModel->namedId();
          $userDatasources[] = $dsModel->namedId();
        }
        $this->log(sprintf(
          "User '%s' has access to the datasources '%s'",
          $activeUserName, implode(", ",$userDatasources)
        ), QCL_LOG_ACL);
      }
      catch( qcl_data_model_RecordNotFoundException $e )
      {
        $this->log("User '$activeUserName' has no access to a datasource.", QCL_LOG_ACL);
      }
    }

    /*
     * return unique list
     */
    sort( $datasources );
    return array_unique( $datasources );
  }

  /**
   * Checks if user has access to the given datasource. If not,
   * throws JsonRpcException.
   * @param string $datasource
   * @return void
   * @throws JsonRpcException
   */
  public function checkDatasourceAccess( $datasource )
  {
    if ( $this->controlDatasourceAccess === true and
        ! in_array( $datasource, $this->getAccessibleDatasources() ) )
    {
      $dsModel = $this->getDatasourceModel( $datasource );
      throw new JsonRpcException( $this->tr("You don't have access to '%s'", $dsModel->getName() ) );
    }
  }

  //-------------------------------------------------------------
  // access control on the model-level
  //-------------------------------------------------------------


  /**
   * Adds an acl ruleset to the controller
   * @param string $type
   * @param array $ruleset Array of Maps
   * @throws InvalidArgumentException
   * @return void
   */
  protected function addAclRuleset( $type, array $ruleset )
  {
    if ( ! in_array( $type, $this->aclTypes ) )
    {
      throw new InvalidArgumentException("Invalid acl type '$type'");
    }
    foreach( $ruleset as $acl )
    {
      $this->acl[$type][] = $acl;
    }
  }

  /**
   * Adds a model acl ruleset to the controller
   * @param array $ruleset
   * @return void
   */
  protected function addModelAcl( array $ruleset )
  {
    $this->addAclRuleset("model", $ruleset );
  }

  /**
   * Returns the acl rulesets for models
   * @throws JsonRpcException
   * @return array Array of maps
   */
  protected function getModelAcl()
  {
    $modelAcl = $this->acl['model'];
    if ( ! is_array( $modelAcl ) or ! count( $modelAcl ) )
    {
      throw new JsonRpcException("No model ACL defined for " . $this->className() );
    }
    return $modelAcl;
  }

  /**
   * Adds a record acl ruleset to the controller
   * @param array $ruleset
   * @return void
   */
  protected function addRecordAcl( array $ruleset )
  {
    $this->addAclRuleset("record", $ruleset );
  }

  /**
   * Returns the acl rulesets for models
   * @return array Array of maps
   */
  protected function getRecordAcl()
  {
    $recordAcl = isset($this->acl['record'])?$this->acl['record']:null;
    return $recordAcl;
  }

  /**
   * Checks if any record acl rules have been set up.
   * @return bool
   */
  protected function hasRecordAcl()
  {
    return count( $this->getRecordAcl() ) > 0;
  }

  /**
   * Returns the model object, given datasource and model type. If both
   * arguments are NULL, return the datasource model object itself.
   * This method checks whether the role of the current user is allowed
   * to access the model as set up in the "acl" property of the class.
   *
   * @param string $datasource
   * @param string $modelType
   * @return qcl_data_model_AbstractActiveRecord
   * @throws qcl_access_AccessDeniedException
   * @todo Rename this to make it clearer that this a protected model access
   */
  protected function getModel( $datasource, $modelType )
  {
    qcl_assert_valid_string( $datasource, "Invalid datasource argument" );
    qcl_assert_valid_string( $modelType, "Invalid model type argument" );

    /*
     * check access to model
     */
    $activeUser  = $this->getActiveUser();
    $roles       = $activeUser->roles();
    $modelAcl    = $this->getModelAcl();
    $access = false;

    foreach( $modelAcl as $ruleset )
    {
      /*
       * check if datasource and model type matches
       */
      if ( ( ! isset( $ruleset['datasource'] )
               or in_array( $datasource, (array) $ruleset['datasource'] )
                  or $ruleset['datasource'] == "*" )
            and ( in_array( $modelType, (array) $ruleset['modelType'] )
              or $ruleset['modelType']  == "*" ) )
      {

        /*
         * check if 'roles' property exists and if yes, if it matches
         */
        if ( ! isset( $ruleset['roles'] ) or $ruleset['roles'] == "*"
              or count( array_intersect( $roles, (array) $ruleset['roles'] ) ) )
        {
          $access = true;
          break;
        }
      }
    }

    if ( ! $access )
    {
      $this->warn( sprintf(
        "User '%s' (role %s) has no access to datasource '%s'/ model type '%s'.",
        $activeUser->username(), implode(",", $roles), $datasource, $modelType
      ) );
      throw new qcl_access_AccessDeniedException("Access denied");
    }

    /*
     * get datasource model by name
     */
    $model = $this->getDatasourceModel( $datasource );

    /*
     * check schema if given
     * todo: does this make sense here or must be a selection criteria?
     */
    if ( isset( $ruleset['schema'] ) )
    {
      if ( ! in_array( $model->getSchema(), (array) $ruleset['schema']) )
      {
        throw new qcl_access_AccessDeniedException("Wrong schema!");
      }
    }

    /*
     * get model type
     */
    if( $modelType )
    {
      $model = $model->getInstanceOfType( $modelType );
    }

    /*
     * initialize model
     */
    $model->init();

    return $model;
  }


  /**
   * Checks acces to the given model properties
   * @param string $accessType
   * @param string|null $datasource
   * @param string|null $modelType
   * @param array $properties
   * @throws qcl_access_AccessDeniedException
   * @throws JsonRpcException
   * @throws InvalidArgumentException
   * @return void
   */
  protected function checkAccess( $accessType, $datasource, $modelType, $properties )
  {
    if ( ! is_array( $properties ) and $properties != "*" )
    {
      throw new InvalidArgumentException("Invalid 'properties' argument. Must be array or '*'." );
    }

    $activeUser  = $this->getActiveUser();
    $roles       = $activeUser->roles();
    $modelAcl    = $this->getModelAcl();
    $access  = false;
    foreach( $modelAcl as $ruleset )
    {
      /*
       * check if datasource and model type matches
       */
      if ( ( ! isset( $ruleset['datasource'] )
              or in_array( $datasource, (array) $ruleset['datasource'] )
                or $ruleset['datasource'] == "*" )
            and ( in_array( $modelType, (array) $ruleset['modelType'] )
              or $ruleset['modelType']  == "*" ) )
      {

        /*
         * examine the rules
         * @todo check if rules OR roles are defined
         */
        $rules =  $ruleset['rules'];
        foreach ( $rules as $rule )
        {

          /*
           * roles, types and properties can take a "*"
           * to match all,
           */
          $accessRoles = $rule['roles'];
          $accessTypes = $rule['access'];
          $accesProps  = $rule['properties'];


          /*
           * does rule match the the access type ?
           */
          if ( $accessTypes == "*" or in_array( $accessType, (array) $accessTypes ) )
          {

            /*
             * does rule also match the given roles?
             */
            if ( $accessRoles == "*" or count( array_intersect( $accessRoles, (array) $roles  ) ) )
            {
              /*
               * finally, does rule match given properties?
               */
              if ( isset( $accesProps['allow'] ) )
              {
                if ( $accesProps['allow'] == "*" or
                    count( (array) $properties ) == count( array_intersect( $accesProps['allow'], (array) $properties ) ) )
                {
                  $access = true;
                  break;
                }
              }
              elseif ( isset( $accesProps['deny'] ) )
              {
                if ( ! count( array_intersect( $accesProps['deny'], (array) $properties ) ) )
                {
                  $access = true;
                  break;
                }
              }
              else
              {
                throw new JsonRpcException( "Acl rule must have a properties/allow or properties/deny element");
              }
            }
          }
        }
      }
    }

    if ( ! $access )
    {
      $this->warn( sprintf(
        "User '%s' (role %s) has no '%s' access to the records or to one or more of the properties [%s] in datatsource '%s'/ model type '%s'.",
        $activeUser->username(), implode(",", $roles ),
        $accessType, implode(",", (array) $properties ),
        $datasource, $modelType
      ) );
      throw new qcl_access_AccessDeniedException("Access denied.");
    }
  }

  /**
   * Checks if there is a rule affecting access to the given record
   * @param string $datasource
   * @param string $modelType
   * @param array $record
   * @throws JsonRpcException
   * @return bool
   */
  protected function hasRecordAccess( $datasource, $modelType, $record )
  {
    /*
     * static variable to cache access rules
     */
    static $cache = array();
    $rule = null;

    /*
     * did we cache the rule already?
     */
    if ( ! isset( $cache[$datasource][$modelType] ) )
    {
      $recordAcl = $this->getRecordAcl();
      foreach( $recordAcl as $ruleset )
      {
        /*
         * look for the first ruleset where datasource and model type
         * matches and save rules in the cache to speed up rule lookups
         */
        if ( ( ! isset( $ruleset['datasource'] )
                or in_array( $datasource, (array) $ruleset['datasource'] )
                  or $ruleset['datasource'] == "*" )
              and ( in_array( $modelType, (array) $ruleset['modelType'] )
                or $ruleset['modelType']  == "*" ) )
        {
          if ( isset( $ruleset['rules'] ) and is_array( $ruleset['rules'] ) )
          {
            $cache[$datasource][$modelType] = $ruleset['rules'];
            break;
          }
          else
          {
            throw new JsonRpcException("No rules defined in record acl for $datasource/$modelType.");
          }
        }
      }
    }

    /*
     * test all the rules against the record. return false when the
     * first test fails.
     */
    $rules = $cache[$datasource][$modelType];
    foreach( $rules as $rule )
    {
      /*
       * look for callback
       * @todo implement other types of rules
       */
      if( isset( $rule['callback' ] ) )
      {
        $callback = $rule['callback' ];
        if ( ! method_exists( $this, $callback ) )
        {
          throw new JsonRpcException("Invalid callback '$callback' defined in record acl for $datasource/$modelType.");
        }

        if ( ! $this->$callback( $datasource, $modelType, $record ) )
        {
          return false;
        }
      }
      else
      {
        throw new JsonRpcException("No callback defined in record acl for $datasource/$modelType.");
      }
    }
    /*
     * passed all tests
     */
    return true;
  }


  /**
   * Checks if there is a rule affecting access to the given record and
   * throws an exception if access to this record is denied
   * @param string $datasource
   * @param string $modelType
   * @param array $record
   * @throws qcl_access_AccessDeniedException
   * @return void
   */
  protected function checkRecordAccess( $datasource, $modelType, $record )
  {
    if ( ! $this->hasRecordAcl() ) return;
    if ( ! $this->hasRecordAccess( $datasource, $modelType, $record ) )
    {
      throw new qcl_access_AccessDeniedException("Access to model record denied.");
    }
  }

  //-------------------------------------------------------------
  // Service API
  //-------------------------------------------------------------

  /**
   * Returns data on service and model type that provides data for the
   * given datasource.
   *
   * @param string $datasource
   * @param $modelType
   * @throws JsonRpcException
   * @return array
   */
  public function method_getModelInfo( $datasource, $modelType )
  {
    $datasourceModel = $this->getDatasourceModel( $datasource );
    $serviceName = $datasourceModel->getServiceNameForType( $modelType );
    if ( ! $serviceName )
    {
      throw new JsonRpcException( sprintf(
        "No service defined for datasource class %s, model type %s",
         $datasourceModel->className(), $modelType
      ) );
    }
    return array(
      'serviceName' => $serviceName
    );
  }


  /**
   * Creates a record in the given model.
   *
   * @param string $datasource
   * @param string $modelType
   * @param object $data
   * @return int Id of the new model record
   */
  public function method_createRecord( $datasource, $modelType, $data )
  {
    /*
     * check access to model and get model
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
     * specifically check authorization to create a record
     */
    $properties = array_keys( get_object_vars( $data ) );
    $this->checkAccess( QCL_ACCESS_CREATE, $datasource, $modelType, $properties );

    /*
     * create it
     */
    return $model->create( $data );
  }

  /**
   * Updates a record in the given model.
   *
   * @param string $datasource
   * @param string $modelType
   * @param int|string $id Numeric id or string named id, depending on model
   * @param object $data
   * @return string "OK" if successful
   */
  public function method_updateRecord( $datasource, $modelType, $id, $data )
  {
    /*
     * check access to model and get model
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
     * specifically check authorization to create a record
     */
    $properties = array_keys( get_object_vars( $data ) );
    $this->checkAccess( QCL_ACCESS_WRITE, $datasource, $modelType, $properties );

    /*
     * load and update it. this will throw an error if it doesn't exist
     * or if access to this model is not allowed
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    $model->set( $data );
    $model->save();
    return "OK";
  }

  /**
   * Deletes a record in the given model.
   *
   * @param string $datasource
   * @param string $modelType
   * @param int|string $id Numeric id or string named id, depending on model
   * @return string "OK" if successful
   */
  public function method_deleteRecord( $datasource, $modelType, $id )
  {
    /*
     * check access to model and get model
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
     * specifically check authorization to create a record
     */
    $this->checkAccess( QCL_ACCESS_DELETE, $datasource, $modelType, "*" );

    /*
     * load and update it. this will throw an error if it doesn't exist
     * or if access is not allowed
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    $model->delete();
    return "OK";
  }

  /**
   * Returns the result of a "fetchAll" operation on the given model of the
   * given datasource.
   *
   * @param string $datasource
   * @param string $modelType
   * @param object $query Must be an qcl_data_db_Query - like object
   * @throws InvalidArgumentException
   * @return array
   */
  public function method_fetchRecords( $datasource, $modelType, $query )
  {
    /*
     * check arguments
     */
    if ( ! $query instanceof qcl_data_db_Query )
    {
      if ( is_object( $query ) )
      {
        $query = new qcl_data_db_Query( object2array( $query )  );
      }
      else
      {
        throw new InvalidArgumentException("Invalid query data.");
      }
    }

    /*
     * check access to model and get it
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
     * check read access to properties
     */
    $properties = $query->getProperties();
    $this->checkAccess( QCL_ACCESS_READ, $datasource, $modelType, $properties );

    /*
     * add 'id' property if not already there
     */
    if ( ! in_array( "id", $properties ) )
    {
      array_unshift( $properties, "id" );
      $query->setProperties( $properties );
    }

    /*
     * check read access to properties in "where" clause
     */
    $where = $query->getWhere();
    if ( $where )
    {
      if ( ! is_array(  $where ) or ! count( $where )  )
      {
        throw new InvalidArgumentException( "Invalid 'where' data.");
      }
      $whereProps = array_keys( $where );
      $this->checkAccess( QCL_ACCESS_READ, $datasource, $modelType, $whereProps );
    }

    /*
     * allow subclasses to manipulate the query object
     */
    $query = $this->fetchRecordsQueryCallback( $datasource, $modelType, $query );

    /*
     * do the query
     */
    $data = $model->getQueryBehavior()->fetchAll( $query );

    /*
     * if no record acl rules have been set up, return unfiltered data
     */
    if ( ! $this->hasRecordAcl() )
    {
      return $data;
    }

    /*
     * otherwise filter rows to which the access in not allowed
     */
    $filteredData = array();
    for ( $i=0; $i<count($data); $i++)
    {
      if ( $this->hasRecordAccess( $datasource, $modelType, $data[$i] ) )
      {
        $filteredData[] = $data[$i];
      }
      else
      {
        //$this->debug( "Ignoring " . $data[$i]['data']);
      }
    }
    return $filteredData;
  }

  /**
   * Hook for subclasses to do something with the query passed
   * to the fetchRecords service method before the query is
   * executed.
   *
   * @param string $datasource
   * @param string $modelType
   * @param qcl_data_db_Query $query
   * @return qcl_data_db_Query By default, simply pass back the object
   */
  protected function fetchRecordsQueryCallback( $datasource, $modelType,  qcl_data_db_Query $query )
  {
    return $query;
  }

  /**
   * Returns the values of a property that matches a where condition
   * @param string $datasource
   * @param string $modelType
   * @param string $property
   * @param object $where
   * @return array
   */
  public function method_fetchValues( $datasource, $modelType, $property, $where )
  {
    $model = $this->getModel( $datasource, $modelType );
    $model->findWhere( object2array( $where ) );
    $result = array();
    if ( $this->hasRecordAcl() )
    {
      while( $model->loadNext() )
      {
        if ( $this->hasRecordAccess( $datasource, $modelType, $model->data() ) )
        {
          $result[] = $model->get( $property );
        }
      }
      return $result;
    }
    else
    {
      return $model->getQueryBehavior()->fetchValues( $property, object2array( $where ) );
    }
  }

  /**
   * Returns the value of a property of a record identified by the id.
   * Throws an error if no access to the property.
   * @param string $datasource
   * @param string $modelType
   * @param string $id
   * @param $property
   * @throws InvalidArgumentException
   * @return mixed
   */
  public function method_getValue( $datasource, $modelType, $id, $property )
  {
    /*
     * get model and check whether the id is numeric or a string
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
     * Check property
     */
    if ( ! $model->hasProperty( $property ) )
    {
      throw new InvalidArgumentException("Model '$model' has no property '$property' !");
    }

    /*
     * Check property-level access
     */
    $this->checkAccess( QCL_ACCESS_READ, $datasource, $modelType, array( $property ) );

    /*
     * Run query
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    return $model->get($property);
  }

  /**
   * Sets the value of a property of a record identified by the id. Throws
   * an error if no access to that property.
   * @param string $datasource
   * @param string $modelType
   * @param string $id
   * @param $property
   * @param $value
   * @throws InvalidArgumentException
   * @return mixed
   */
  public function method_setValue( $datasource, $modelType, $id, $property, $value )
  {
    /*
     * get model and check whether the id is numeric or a string
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
     * Check property
     */
    if ( ! $model->hasProperty( $property ) )
    {
      throw new InvalidArgumentException("Model '$model' has no property '$property' !");
    }

    /*
     * check property-level access
     */
    $this->checkAccess( QCL_ACCESS_WRITE, $datasource, $modelType, array( $property ) );

    /*
     * Run query
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    $model->set($property, $value);
    $model->save();
  }


  //-------------------------------------------------------------
  // datasources
  //-------------------------------------------------------------

  /**
   * Getter for datasource manager object
   * @return qcl_data_datasource_Manager
   */
  public function getDatasourceManager()
  {
    return qcl_data_datasource_Manager::getInstance();
  }

  /**
   * Returns the  datasource model with the datasource connection
   * data preloaded.
   *
   * @param string $datasource
   * @return qcl_data_datasource_DbModel
   */
  public function getDatasourceModel( $datasource=null )
  {
    if ( $datasource )
    {
      return $this->getDatasourceManager()->getDatasourceModelByName( $datasource );
    }
    else
    {
      return $this->getDatasourceManager()->getDatasourceModel();
    }

  }

  //-------------------------------------------------------------
  // creating and evaluating form data
  //-------------------------------------------------------------

  /**
   * Returns data for a dialog.Form widget based on a model
   * @param qcl_data_model_AbstractActiveRecord $model
   * @param int $width The default width of the form in pixel (defaults to 300)
   * @throws JsonRpcException
   * @throws InvalidArgumentException
   * @return array
   */
  protected function createFormData( qcl_data_model_AbstractActiveRecord $model, $width=300 )
  {
    $modelFormData = $model->formData();

    if ( ! is_array( $modelFormData) or ! count( $modelFormData ) )
    {
      throw new JsonRpcException( "No form data exists.");
    }

    $formData = array();

    foreach( $modelFormData as $name => $elementData )
    {
      /*
       * dynamically get element data from the object
       */
      if ( isset( $elementData['delegate'] ) )
      {
        qcl_assert_array( $elementData['delegate'] );
        foreach( $elementData['delegate'] as $key => $delegateMethod )
        {
          qcl_assert_method_exists( $model, $delegateMethod );
          $elementData[$key] = $model->$delegateMethod( $name, $key, $elementData );
        }
        unset( $elementData['delegate'] );
      }

      /*
       * check property data
       */
      qcl_assert_valid_string( $elementData['label'] );

      /*
       * type
       */
      if ( ! isset( $elementData['type'] ) )
      {
        $elementData['type']  = "TextField";
      }

      /*
       * width
       */
      if ( ! isset( $elementData['width'] ) )
      {
        $elementData['width'] = $width;
      }

      /*
       * get value from model or default value
       */
      if ( ! isset( $elementData['value'] ) )
      {
         $elementData['value'] = $model->get( $name );
      }
      if ( isset( $elementData['default'] ) )
      {
        if ( ! $elementData['value'] )
        {
          $elementData['value'] = $elementData['default'];
        }
        unset( $elementData['default'] );
      }

      /*
       * marshal value
       */
      if ( isset( $elementData['marshaler'] ) )
      {
        if ( isset( $elementData['marshaler']['marshal'] ) )
        {
          $marshaler = $elementData['marshaler']['marshal'];
          if( isset( $marshaler['function'] ) )
          {
            $elementData['value'] = $marshaler['function']( $elementData['value'] );
          }
          elseif( isset( $marshaler['callback'] ) )
          {
            $callback = $marshaler['callback'];
            qcl_assert_array( $callback );
            if ( $callback[0] == "this" )
            {
              $callback[0] = $model;
            }
            qcl_assert_method_exists( $callback[0], $callback[1] );
            $elementData['value'] = $callback[0]->$callback[1]( $elementData['value'] );
          }
          else
          {
            throw new InvalidArgumentException("Invalid marshalling data");
          }
        }
        unset( $elementData['marshaler'] );
      }
      $formData[ $name ] = $elementData;
    }
    return $formData;
  }

  /**
   * Parses data returned by  dialog.Form widget based on a model
   * @param qcl_data_model_AbstractActiveRecord $model
   * @param object $data ;
   * @throws JsonRpcException
   * @throws InvalidArgumentException
   * @return array
   */
  protected function parseFormData( qcl_data_model_AbstractActiveRecord $model, $data )
  {
    $data = object2array( $data ) ;
    $modelFormData = $model->formData();

    if ( ! is_array( $modelFormData) or ! count( $modelFormData ) )
    {
      throw new JsonRpcException( "No form data exists");
    }
    foreach( $data as $property => $value )
    {
      /*
       * is it an editable property?
       */
      if (! isset( $modelFormData[$property] ) )
      {
        throw new JsonRpcException( "Invalid form data property '$property'");
      }

      /*
       * should I ignore it?
       */
      if ( isset( $modelFormData[$property]['ignore'] ) and $modelFormData[$property]['ignore'] === true )
      {
        unset( $data[$property] );
        continue;
      }

      /*
       * marshaler
       */
      if ( isset( $modelFormData[$property]['marshaler']['unmarshal'] ) )
      {
        $marshaler = $modelFormData[$property]['marshaler']['unmarshal'];
        if( isset( $marshaler['function'] ) )
        {
          $value = $marshaler['function']( $value );
        }
        elseif( isset( $marshaler['callback'] ) )
        {
          $callback = $marshaler['callback'];
          qcl_assert_array( $callback );
          if ( $callback[0] === "this" )
          {
            $callback[0] = $model;
          }
          qcl_assert_method_exists( $callback[0], $callback[1] );
          $value = $callback[0]->$callback[1]( $value );
        }
        else
        {
          throw new InvalidArgumentException("Invalid marshaler data");
        }
        $data[$property] = $value;
      }

      /*
       * remove null values from data
       */
      if ( $value === null )
      {
        unset( $data[$property] );
      }
    }
    return $data;
  }
}
