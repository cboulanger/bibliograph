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

use Yii;

use JsonRpc2\Controller;
use JsonRpc2\extensions\AuthException;

use app\models\User;
use app\models\Session;

/**
 * Service class providing methods to get or set configuration
 * values
 */
class AppController extends Controller
{
  use \JsonRpc2\extensions\AuthTrait;

  /**
   * Returns the [[app\models\User]] instance of the user with the given
   * username.
   *
   * @param string $username
   * @throws InvalidArgumentException if user does not exist
   * @return \app\models\User
   */
  public function user($username)
  {
    $user = User::findOne(['namedId'=>$username]);
    if (is_null($user)) {
      throw new \InvalidArgumentException( $this->tr("User '$username' does not exist.") );
    }
    return $user;
  }

  /**
   * Shorthand getter for active user object
   * @return \app\models\User
   */
  public function getActiveUser()
  {
    return Yii::$app->user->identity;
  }

  /**
   * Tries to continue an existing session
   *
   * @param \app\models\User $user
   * @return bool If an existing session could be continued
   */
  protected function continueUserSession( $user )
  {
    $session = Session::findOne(['UserId' => $user->id]);
    if( $session ) {
      // manually set session id to recover the session data
      session_id( $session->namedId );
    }
    Yii::$app->session->open();
    return (bool) $session; 
  }

  /**
   * Filter method to protect action methods from unauthorized access
   *
   * @param \yii\base\Action $action
   * @return boolan True if action can proceed, false if not
   */
  public function beforeAction($action)
  {
    if (!parent::beforeAction($action)) {
      return false;
    }

    // authenticate action is always allowed
    if (in_array($action->id, ["authenticate"])) {
      return true;
    }

    // on-the-fly authentication with access token
    $token = $this->getAuthCredentials();
    if (!$token or ! $user = User::findIdentityByAccessToken($token)) {
      return false;
      // @todo this doesn't work:
      // throw new AuthException('Missing authentication', AuthException::MISSING_AUTH);
    }

    // log in user 
    Yii::$app->user->setIdentity($user);
    $this->continueUserSession( $user );
    $sessionId = $this->getSessionId();
    Yii::info("Authenticated user '{$user->namedId}' via auth auth token (Session {$sessionId}.");
    return true;
  }

  /**
   * Shorthand getter for  the current session id.
   * @return string
   */
  public function getSessionId()
  {
    return Yii::$app->session->getId();
  }

  /**
   * Checks if active user has the given permission.
   * @param $permission
   * @return bool
   */
  public function activeUserhasPermission($permission)
  {
    return $this->getActiveUser()->hasPermission( $permission );
  }

  /**
   * Checks if active user has the given permission and aborts if
   * permission is not granted.
   *
   * @param string $permission
   * @return bool
   * @throws Exception if access is denied
   */
  public function requirePermission($permission)
  {
    if (!  $this->activeUserhasPermission( $permission )) {
      $this->warn( sprintf(
      "Active user %s does not have required permission %s",
      $this->getActiveUser(), $permission
      ) );
        throw new Exception("Access denied.");
    }
  }

  /**
   * Shorthand method to check if active user has a role
   * @param string $role
   * @return bool
   */
  public function hasRole($role)
  {
    return $this->getActiveUser()->hasRole( $role );
  }

  /**
   * Shorthand method to enforce if active user has a role
   * @param string $role
   * @throws qcl_access_AccessDeniedException
   * @return bool
   */
  public function requireRole($role)
  {
    if (!  $this->hasRole( $role )) {
      $this->warn( sprintf(
      "Active user %s does hat required role %s",
      $this->getActiveUser(), $role
      ) );
        throw new Exception("Access denied.");
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
    not_implemented();
    static $datasources = null;

    if ($datasources === null) {
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
  public function checkDatasourceAccess($datasource)
  {
    if ($this->controlDatasourceAccess === true and
    ! in_array( $datasource, $this->getAccessibleDatasources() ) ) {
      $dsModel = $this->getDatasourceModel( $datasource );
      throw new JsonRpcException( $this->tr("You don't have access to '%s'", $dsModel->getName() ) );
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
  public function method_getModelInfo($datasource, $modelType)
  {
    $datasourceModel = $this->getDatasourceModel( $datasource );
    $serviceName = $datasourceModel->getServiceNameForType( $modelType );
    if (! $serviceName) {
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
  public function method_createRecord($datasource, $modelType, $data)
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
  public function method_updateRecord($datasource, $modelType, $id, $data)
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
  public function method_deleteRecord($datasource, $modelType, $id)
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
  public function method_fetchRecords($datasource, $modelType, $query)
  {
    /*
   * check arguments
     */
    if (! $query instanceof qcl_data_db_Query) {
      if (is_object( $query )) {
        $query = new qcl_data_db_Query( object2array( $query )  );
      } else {
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
    if (! in_array( "id", $properties )) {
      array_unshift( $properties, "id" );
      $query->setProperties( $properties );
    }

    /*
   * check read access to properties in "where" clause
     */
    $where = $query->getWhere();
    if ($where) {
      if (! is_array(  $where ) or ! count( $where )) {
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
    if (! $this->hasRecordAcl()) {
      return $data;
    }

    /*
   * otherwise filter rows to which the access in not allowed
     */
    $filteredData = array();
    for ($i=0; $i<count($data); $i++) {
      if ($this->hasRecordAccess( $datasource, $modelType, $data[$i] )) {
        $filteredData[] = $data[$i];
      } else {
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
  protected function fetchRecordsQueryCallback($datasource, $modelType, qcl_data_db_Query $query)
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
  public function method_fetchValues($datasource, $modelType, $property, $where)
  {
    $model = $this->getModel( $datasource, $modelType );
    $model->findWhere( object2array( $where ) );
    $result = array();
    if ($this->hasRecordAcl()) {
      while ($model->loadNext()) {
        if ($this->hasRecordAccess( $datasource, $modelType, $model->data() )) {
          $result[] = $model->get( $property );
        }
      }
      return $result;
    } else {
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
  public function method_getValue($datasource, $modelType, $id, $property)
  {
    /*
   * get model and check whether the id is numeric or a string
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * Check property
     */
    if (! $model->hasProperty( $property )) {
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
  public function method_setValue($datasource, $modelType, $id, $property, $value)
  {
    /*
   * get model and check whether the id is numeric or a string
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * Check property
     */
    if (! $model->hasProperty( $property )) {
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
  public function getDatasourceModel($datasource = null)
  {
    if ($datasource) {
      return $this->getDatasourceManager()->getDatasourceModelByName( $datasource );
    } else {
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
  protected function createFormData(qcl_data_model_AbstractActiveRecord $model, $width = 300)
  {
    $modelFormData = $model->formData();

    if (! is_array( $modelFormData) or ! count( $modelFormData )) {
      throw new JsonRpcException( "No form data exists.");
    }

    $formData = array();

    foreach ($modelFormData as $name => $elementData) {
      /*
       * dynamically get element data from the object
       */
      if (isset( $elementData['delegate'] )) {
        qcl_assert_array( $elementData['delegate'] );
        foreach ($elementData['delegate'] as $key => $delegateMethod) {
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
      if (! isset( $elementData['type'] )) {
        $elementData['type']  = "TextField";
      }

      /*
       * width
       */
      if (! isset( $elementData['width'] )) {
        $elementData['width'] = $width;
      }

      /*
       * get value from model or default value
       */
      if (! isset( $elementData['value'] )) {
        $elementData['value'] = $model->get( $name );
      }
      if (isset( $elementData['default'] )) {
        if (! $elementData['value']) {
          $elementData['value'] = $elementData['default'];
        }
        unset( $elementData['default'] );
      }

      /*
       * marshal value
       */
      if (isset( $elementData['marshaler'] )) {
        if (isset( $elementData['marshaler']['marshal'] )) {
          $marshaler = $elementData['marshaler']['marshal'];
          if (isset( $marshaler['function'] )) {
            $elementData['value'] = $marshaler['function']( $elementData['value'] );
          } elseif (isset( $marshaler['callback'] )) {
            $callback = $marshaler['callback'];
            qcl_assert_array( $callback );
            if ($callback[0] == "this") {
              $callback[0] = $model;
            }
            qcl_assert_method_exists( $callback[0], $callback[1] );
            $elementData['value'] = $callback[0]->$callback[1]( $elementData['value'] );
          } else {
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
  protected function parseFormData(qcl_data_model_AbstractActiveRecord $model, $data)
  {
    $data = object2array( $data ) ;
    $modelFormData = $model->formData();

    if (! is_array( $modelFormData) or ! count( $modelFormData )) {
      throw new JsonRpcException( "No form data exists");
    }
    foreach ($data as $property => $value) {
      /*
       * is it an editable property?
       */
      if (! isset( $modelFormData[$property] )) {
        throw new JsonRpcException( "Invalid form data property '$property'");
      }

      /*
       * should I ignore it?
       */
      if (isset( $modelFormData[$property]['ignore'] ) and $modelFormData[$property]['ignore'] === true) {
        unset( $data[$property] );
        continue;
      }

      /*
       * marshaler
       */
      if (isset( $modelFormData[$property]['marshaler']['unmarshal'] )) {
        $marshaler = $modelFormData[$property]['marshaler']['unmarshal'];
        if (isset( $marshaler['function'] )) {
          $value = $marshaler['function']( $value );
        } elseif (isset( $marshaler['callback'] )) {
          $callback = $marshaler['callback'];
          qcl_assert_array( $callback );
          if ($callback[0] === "this") {
            $callback[0] = $model;
          }
          qcl_assert_method_exists( $callback[0], $callback[1] );
          $value = $callback[0]->$callback[1]( $value );
        } else {
          throw new InvalidArgumentException("Invalid marshaler data");
        }
        $data[$property] = $value;
      }

      /*
       * remove null values from data
       */
      if ($value === null) {
        unset( $data[$property] );
      }
    }
    return $data;
  }

  //-------------------------------------------------------------
  // shim methods
  // @todo replace by Yii methods
  //-------------------------------------------------------------
  
  public function log($msg)
  {
    Yii:trace($msg);
  }  
  public function debug($msg)
  {
    Yii:trace($msg);
  }
  public function info($msg)
  {
    Yii:info($msg);
  }
  public function warn($msg)
  {
    Yii:warning($msg);
  }
  public function error($msg)
  {
    Yii:error($msg);
  }
  protected function tr($string)
  {
    return Yii::t('app', $string );
  }
}
