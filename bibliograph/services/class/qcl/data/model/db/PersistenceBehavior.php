<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */
qcl_import( "qcl_core_IPersistenceBehavior" );
qcl_import( "qcl_data_model_db_ActiveRecord" );

/**
 * Persistence behavior singleton which is bases on a db-based
 * ActiveRecord object. Saves the serialized object properties
 * into a blob column.
 */
class qcl_data_model_db_PersistenceBehavior
  extends    qcl_data_model_db_ActiveRecord
  implements qcl_core_IPersistenceBehavior
{

  //-------------------------------------------------------------
  // Static members
  //-------------------------------------------------------------

  /**
   * Return singleton instance of this class
   * @return qcl_data_model_db_PersistenceBehavior
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  //-------------------------------------------------------------
  // Object Properties
  //-------------------------------------------------------------


  //-------------------------------------------------------------
  // Model Properties
  //-------------------------------------------------------------

  protected $tableName = "data_Persistence";

  private $properties = array(
    "class" => array(
      "check"   => "string",
      "sqltype" => "varchar(50)"
    ),
    "objectId"  => array(
      "check"   => "string",
      "sqltype" => "varchar(50)"
    ),
    "data" => array(
      "check"     => "string",
      "sqltype"   => "longblob"
    ),
    "userId" => array(
      "check"     => "integer",
      "sqltype"   => "int(11)",
      "nullable"  => true
    ),
    "sessionId" => array(
      "check"     => "string",
      "sqltype"   => "varchar(40)",
      "nullable"  => true
    )
  );

  /**
   * The indexes
   * @var array
   */
  private $indexes = array(
    "class_objectId_userId_sessionId" => array(
      "type"        => "unique",
      "properties"  => array( "class","objectId","userId","sessionId" )
    )
  );

  /**
   * Relations
   * @var array
   * @todo implement plug-in relations
   */
//  private $relations = array(
//    'session' => array(
//      'type'    => "n:1",
//      'target'  => array( 'model' => "qcl_access_model_Session" )
//    ),
//    'user'   => array(
//      'type'    => "n:1",
//      'target'  => array( 'model' => "qcl_access_model_User" )
//    )
//  );

  //-------------------------------------------------------------
  // Constructor
  //-------------------------------------------------------------

  /**
   * Constructor, adds properties
   */
  function __construct( )
  {
    $this->getPropertyBehavior()->reset();
    $this->addProperties( $this->properties );
    $this->addIndexes( $this->indexes );
    //$this->addRelations( $this->relations, __CLASS__ );
    parent::__construct();
  }

  //-------------------------------------------------------------
  // getters & setters
  //-------------------------------------------------------------


  //-------------------------------------------------------------
  // qcl_core_IPersistable interface methods
  //-------------------------------------------------------------

  /**
   * Loads the object's public properties from the session
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   * @return boolean Whether object data has been found and restored (true)
   *  or not (false)
   */
  public function restore( $object, $id )
  {

    $sessionId = $this->getSessionIdValue( $object );
    $userId    = $this->getUserIdValue( $object );

    try
    {
      /*
       * load model record
       */
      $this->loadWhere( array(
        "class"     => get_class($object),
        "objectId"  => $id,
        "sessionId" => $sessionId,
        "userId"    => $userId
      ) );

      qcl_log_Logger::getInstance()->log( sprintf(
        "%s: restoring properties from id #%s",
        $object->className(), $id
      ), QCL_LOG_PERSISTENCE );

      /*
       * restore properties
       */
      $object->unserialize( $this->get("data") );

      return true;
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      qcl_log_Logger::getInstance()->log( $object->className() . ": no cached data with id '$id'",QCL_LOG_PERSISTENCE);
      return false;
    }
  }

  /**
   * Saves the object's public property to the session.
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   */
  public function persist( $object, $id )
  {

    $sessionId = $this->getSessionIdValue( $object );
    $userId    = $this->getUserIdValue( $object );

    /*
     * see if record exists
     */
    $this->findWhere( array(
      "class"     => get_class($object),
      "objectId"  => $id,
      "sessionId" => $sessionId,
      "userId"    => $userId
    ) );

    if( $this->foundSomething() )
    {

      $this->loadNext();
      $this->setData( $object->serialize() );
      $this->save();
// @todo cannot use logging during shutdown, when called from destructor
//      $this->log( sprintf(
//        "%s: updated in cache with id '%s'", $object->className(), $id
//      ), QCL_LOG_APPLICATION );
    }

    /*
     * otherwise, create it
     */
    else
    {
      $this->create( array(
        "class"     => get_class( $object ),
        "objectId"  => $id,
        "sessionId" => $sessionId,
        "userId"    => $userId,
        "data"      => $object->serialize()
      ));
// @todo cannot use logging during shutdown, when called from destructor
//     $this->log( sprintf(
//        "%s: saved to cache with id '%s'", $object->className(), $id
//      ), QCL_LOG_APPLICATION );
    }
  }

  /**
   * Deletes the persistence data for the object with the given id.
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   */
  public function dispose( $object, $id )
  {
    qcl_log_Logger::getInstance()->log( "Deleting persistence data for " . $object->className() . " (id '$id')", QCL_LOG_PERSISTENCE);
    $this->deleteWhere( array(
      "objectId" => $id
    ) );
  }

  /**
   * Returns true if persistent object is bound to the current user
   * @param $object
   * @return boolean
   */
  protected function getUserIdValue( $object )
  {
    $user = $this->getApplication()->getAccessController()->getActiveUser();
    if ( ! $user ) return null;
    $userId = $user->id();
    return $object->isBoundToUser() ? $userId : null;
  }

  /**
   * Returns true if persistent object is bound to the current session
   * @param $object
   * @return boolean
   */
  protected function getSessionIdValue( $object )
  {
    $sessionId = $this->getApplication()->getAccessController()->getSessionId();
    return $object->isBoundToSession() ? $sessionId : null;
  }

  /**
   * Forwards to the property behavior
   */
  public function _get( $property )
  {
    return $this->getPropertyBehavior()->_get( $property );
  }


}
