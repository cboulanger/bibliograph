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

class qcl_event_type_Event
  extends qcl_core_Object
{

  /**
   * Type (name) of event
   * @var string
   */
  var $type;

  /**
   * Id of event target. Only available when the event has
   * been dispached
   * @var qcl_core_Object
   */
  var $targetId;


  /**
   * Constructor
   * @param string $type
   * @return \qcl_event_type_Event
   */
  function __construct( $type )
  {
    $this->type = $type;
  }

  /**
   * Sets the event target by saving its object id.
   * @param $target
   * @return void#
   * @throws LogicException
   */
  function setTarget( $target )
  {
    if ( ! is_a( $target, "qcl_core_Object" ) )
    {
      throw new LogicException("Invalid target object.");
    }
    $this->setTargetId( $target->objectId() );
  }

  /**
   * Returns target object, if exists.
   * @return qcl_core_Object
   */
  function getTarget()
  {
    if ( $this->targetId )
    {
      return $this->getObjectById( $this->getTargetId() );
    }
    return null;
  }

  function setTargetId( $targetId )
  {
    $this->targetId = $targetId;
  }

  function getTargetId()
  {
    return $this->targetId;
  }

}
