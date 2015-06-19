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

qcl_import("qcl_event_type_Event");

class qcl_event_type_DataEvent
  extends qcl_event_type_Event
{

  /**
   * Constructor
   * @param string $type
   * @param $data
   * @return \qcl_event_type_DataEvent
   */
  function __construct( $type, $data )
  {
    $this->type = $type;
    $this->data = $data;
  }

  /**
   * Event Data
   * @var string
   */
  protected $data;

  /**
   * Getter for event data
   * @return mixed
   */
  public function getData()
  {
    return $this->data;
  }

  /**
   * Setter for event data
   */
  public function setData( $data )
  {
    $this->data = $data;
  }
}
