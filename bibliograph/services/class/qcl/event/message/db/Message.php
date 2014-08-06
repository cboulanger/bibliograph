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

qcl_import( "qcl_data_model_db_ActiveRecord" );

/**
 * model for messages stored in a database
 */
class qcl_event_message_db_Message
  extends qcl_data_model_db_ActiveRecord
{

  /**
   * Name of the table storing the data
   * for this model
   * @var string
   */
  protected $tableName = "data_Messages";   
  
  /**
   * The number of seconds after which the record is automatically
   * deleted if not modified. Defaults to null (= no expiration).
   * @var int
   */
	protected $expiresAfter = 60;   

  /**
   * The properties of this model
   * @var array
   */
  private $properties = array(
    'name' => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    ),
    'data' => array(
      'check'     => "string",
      'sqltype'   => "blob"
    )
  );

  /**
   * Relations of the model
   */
  private $relations = array(
    'Message_Session' => array(
      'type'    => QCL_RELATIONS_HAS_ONE,
      'target'  => array(
        'class'     => "qcl_access_model_Session"
      )
    )
  );

  /**
   * Constructor. Adds properties and relations
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
  }

  /**
   * Returns singleton instance.
   * @static
   * @return qcl_event_message_db_Message
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }
}
