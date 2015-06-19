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

qcl_import("qcl_core_Object");

class qcl_event_message_Message
  extends qcl_core_Object
{

  /**
   * Message name
   * @var string
   */
  protected $name;

  /**
   * Message data
   */
  protected $data;

  /**
   * The id of the sender. Only set when the message is dispatched
   * @var string
   */
  protected $senderId;

  /**
   * Constructor
   * @param string $name
   * @param mixed $data
   * @return \qcl_event_message_Message
   */
  public function __construct( $name=null, $data=null )
  {
    $this->name = $name;
    $this->data = $data;
  }

  /**
   * Setter for name. Forbidden.
   */
  public function setName()
  {
    throw new LogicException("Name cannot be changed.");
  }

  /**
   * Getter for name
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Setter for data
   * @param mixed $data
   * @return void
   */
  public function setData( $data )
  {
    $this->data = $data;
  }

  /**
   * Getter for data
   * @return mixed
   */
  public function getData()
  {
    return $this->data;
  }

  /**
   * Setter for sender id
   * @param $senderId
   * @return unknown_type
   */
  public function setSenderId( $senderId )
  {
    $this->senderId = $senderId;
  }

  /**
   * Getter for sender id
   * @return string
   */
  public function getSenderId()
  {
    return $this->senderId;
  }

  /**
   * Stores the sender by storing its id.
   * @param qcl_core_Object $sender
   * @return void
   */
  public function setSender( $sender )
  {
    $this->setSenderId( $sender->objectId() );
  }

  /**
   * Returns the sender object
   * @return qcl_core_Object
   */
  public function getSender()
  {
    return $this->getObjectById( $this->getSenderId() );
  }
}
