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
qcl_import( "qcl_io_filesystem_local_File" );

/**
 * Persistence behavior singleton which is bases on a serialized
 * data in a temporary file.
 */
class qcl_data_file_PersistenceBehavior
  extends    qcl_io_filesystem_local_File
  implements qcl_core_IPersistenceBehavior
{
  
  //-------------------------------------------------------------
  // Static members
  //-------------------------------------------------------------

  /**
   * Return singleton instance of this class
   * @return qcl_data_model_file_PersistenceBehavior
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }
  
  //-------------------------------------------------------------
  // Constructor
  //-------------------------------------------------------------

  /**
   * Constructor
   */
  public function __construct ()
  {
    /*
     * create file
     */
    parent::__construct( $this->getResourcePath() );

    /*
     * if file doesn't already exist, save an empty array;
     */
    if ( ! $this->exists() )
    {
      $this->save(serialize(array()));
    }
  }
  
  /**
   * Return the path to the file in which the persistent data is stored.
   * @return string
   */
  public function getResourcePath()
  {
    $app = qcl_application_Application::getInstance();
    return "file://" . QCL_VAR_DIR . "/" . $app->id() . ".dat";
  }

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
    $data = unserialize( $this->load() );
    if( isset( $data[$id] ) )
    {
      qcl_log_Logger::getInstance()->log( "Loading data from " . $this->getResourcePath() . " with id '$id': " . print_r($data[$id],true),QCL_LOG_PERSISTENCE);
      foreach( $data[$id] as $propName => $propValue)
      {
        try
        {
          $object->set($propName,$propValue);
        }
        catch( qcl_core_PropertyBehaviorException $e )
        {
          qcl_log_Logger::getInstance()->log( "Persisted data with id '$id': cannot set property '$propName'.",QCL_LOG_PERSISTENCE);
        }
      }

      return true;
    }
    else
    {
      qcl_log_Logger::getInstance()->log( $this->getResourcePath() . ": no cached data with id '$id'",QCL_LOG_PERSISTENCE);
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
    $data = unserialize( $this->load() );
    $data[$id]=get_object_vars($object);
    $this->save(serialize($data));
  }

  /**
   * Deletes the persistence data for the object with the given id.
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   */
  public function dispose( $object, $id )
  {
    $data = unserialize( $this->load() );
    unset($data[$id]);
    $this->save(serialize($data));
  }
}
?>