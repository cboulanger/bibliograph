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
qcl_import( "qcl_event_message_Message" );

/**
 * A server message that is forwarded to the client
 */
class qcl_event_message_ClientMessage
  extends qcl_event_message_Message
{

  /**
   * Whether the message should be broadcasted to all connected clients
   * @var boolean
   */
  protected $broadcast = false;
  
  /**
   * Whether the message should be sent to the session of the current user
   * @var boolean
   */
  protected $excludeOwnSession = false;
  
  /**
   * Arbitrary access control information
   * @var array|null
   */
  protected $acl = null;
  
  /**
   * Setter for broadcast
   * @param bool $value
   */
  public function setBroadcast( $value )
  {
    qcl_assert_boolean( $value );
    $this->broadcast = $value;
  }

  /**
   * Getter for broadcast
   */
  public function isBroadcast()
  {
    return $this->broadcast;
  }
  
  /**
   * Pass true if the session of the sender is to be excluded,
   * false if not.
   * @param boolean $value
   */
  public function setExcludeOwnSession( $value )
  {
    qcl_assert_boolean( $value );
    $this->excludeOwnSession = $value;
  }

  /**
   * Returns true if the session of the sender is to be excluded,
   * false if not.
   * @return boolean
   */
  public function isExcludeOwnSession()
  {
    return $this->excludeOwnSession;
  }
  
  /**
   * Sets access control data
   * @param mixed $aclData
   */
  public function setAcl( $aclData )
  {
  	$this->acl = $aclData;
  }
  
  /**
   * Getter for access control data
   * @return mixed
   */
  public function getAcl()
  {
  	return $this->acl;
  }
}
