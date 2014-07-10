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

qcl_import( "qcl_data_model_db_NamedActiveRecord" );
qcl_import( "qcl_event_message_db_Message" ); // FIXME shouldn't be necessary

/**
 * Model for session data bases on a mysql database model.
 * 'Session' here means the connection established by a particular
 * browser instance.
 */
class qcl_access_model_Session
  extends qcl_data_model_db_NamedActiveRecord
{

  /**
   * Table name
   */
  protected $tableName = "data_Session";

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "SessionId";
	
  /**
   * Properties of the model
   * @var array
   */
  private $properties = array(
  
    'parentSessionId' => array(
      'check'     => QCL_PROPERTY_CHECK_STRING,
      'sqltype'   => "varchar(50)",
      'nullable'  => true
    ),
    
    'ip' => array(
      'check'     => QCL_PROPERTY_CHECK_STRING,
      'sqltype'   => "varchar(32)"
    )

//    ,'isToken' => array(
//      'check'     => QCL_PROPERTY_CHECK_BOOLEAN,
//      'sqltype'   => "int(1) DEFAULT 0"
//    )
  );

  /**
   * Indexes
   */
  private $indexes = array(
    "session_index" => array(
      "type"        => "unique",
      "properties"  => array("namedId","ip")
    )
  );

  /**
   * Relations of the model
   */
  private $relations = array(
    'User_Session'  => array(
      'type'    => QCL_RELATIONS_HAS_ONE,
      'target'  => array(
        'class'  => "qcl_access_model_User"
      )
    ),
    'Message_Session' => array(
      'type'    => QCL_RELATIONS_HAS_MANY,
      'target'  => array(
        'class'     => "qcl_event_message_db_Message",
        'dependent' => true
      )
    )
  );

  /**
   * Constructor
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
    $this->addIndexes( $this->indexes );
  }

  /**
   * Returns singleton instance.
   * @static
   * @return qcl_access_model_Session
   */
  public static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  /**
   * Shorthand getter for access behavior
   * @return qcl_access_Controller
   */
  function getAccessController()
  {
    return $this->getApplication()->getAccessController();
  }

  //-------------------------------------------------------------
  // API methods
  //-------------------------------------------------------------

  /**
   * Registers a session. adds an entry with the current timestamp if the session
   * doesn't exist, otherwise leaves the last action column alone.
   *
   * @return void
   * @param string $sessionId The id of the current session
   * @param qcl_access_model_User $user
   * @param string $ip The IP address of the requesting client. This makes sure
   * session ids cannot be "stolen" by an intercepting party
   * @throws qcl_access_InvalidSessionException
   */
  function registerSession( $sessionId, qcl_access_model_User $user, $ip )
  {

    /*
     * if session id is present but linked to a different IP
     * there is a security breach, unless the request was originated
     * on the local host
     * DISABLED
     */
//    $localhost    = ( $ip=="::1" or $ip=="127.0.0.1" or $ip=="0.0.0.0");
//    $sessionMismatch = $this->countWhere( array(
//      'namedId' => $sessionId,
//      'ip'      => array( "!=", $ip )
//    ) );
//
//    if ( 0 or ! $localhost and $sessionMismatch )
//    {
//      if( QCL_ACCESS_ALLOW_IP_MISMATCH )
//      {
//        $this->log( "Origin IP of session has changed to $ip. Ignored as per QCL_ACCESS_ALLOW_IP_MISMATCH setting.", QCL_LOG_AUTHENTICATION);
//      }
//      else
//      {
//        $this->warn( "Origin IP of session has changed to $ip. Access denied.");
//        throw new qcl_access_InvalidSessionException($this->tr("Your IP has changed."));
//      }
//    }

    /*
     * create new session data if it doesn't already exist
     */
    $this->createIfNotExists( $sessionId, array(
      $user->foreignKey()  => $user->id(),
      'ip'                 => $ip
    ) );

    /*
     * update the modified column
     */
    $this->setModified(null);
    $this->save();
  }

  /**
   * Check if user is registered
   * @param $sessionId
   * @param qcl_access_model_User $user
   * @param $ip
   * @return boolean
   */
  function isRegistered( $sessionId, qcl_access_model_User $user, $ip )
  {
    return (bool) $this->countWhere( array(
      'namedId'              => $sessionId,
       $user->foreignKey()   => $user->id(),
      'ip'                   => $ip
    ) );
  }

  /**
   * Returns true if the current record is a token (i.e. a session id which is passed around for later access)
   * @return bool
   */
  function isToken()
  {
    return new qcl_core_NotImplementedException(__METHOD__);
    //return (bool) $this->get("isToken");
  }

  /**
   * Unregisters a session. We cannot delete them right away since
   * the request has not completed yet, so we mark them to be deleted
   * when the next session is registered.
   * @return void
   * @param string $sessionId
   * @throws InvalidArgumentException
   */
  function unregisterSession( $sessionId )
  {
    if ( ! $sessionId )
    {
      throw new InvalidArgumentException("Missing session id");
    }
    try
    {
      $this->load( $sessionId );
      $this->delete();
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      $this->log( "Session #$sessionId does not exist.", QCL_LOG_AUTHENTICATION );
    }
  }
  
  /**
   * Overridden.
   * @see qcl_data_model_AbstractActiveRecord::checkExpiration()
   * todo rename to isExpired()
   */
  protected function checkExpiration()
  {
  	if ( $this->datasourceModel() )
  	{
  		$userModel = $this->datasourceModel()->getInstanceOfType("user");	
  	}
  	else // FIXME
  	{
  		$userModel = $this->getApplication()->getAccessController()->getUserModel();
  	}
  	
  	/*
  	 * delete this record if no corresponding user exists
  	 */
  	try 
  	{
  		$userModel->load( (int) $this->get("UserId") );
  	}
  	catch( qcl_data_model_RecordNotFoundException $e )
  	{
  		$this->delete();
  	}
  	return false;
  }
}
?>