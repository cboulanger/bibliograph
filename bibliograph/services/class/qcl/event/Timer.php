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


class qcl_event_Timer
{

  protected $start;
  protected $end;

  function __construct()
  {
    $this->reset();
  }

  protected function get_microtime()
  {
    $tmp = explode(" ",microtime());
    $rtime = (double)$tmp[0] + (double)$tmp[1];
    return $rtime;
  }

  public function start()
  {
    $this->start = $this->get_microtime();
  }

  public function reset()
  {
    $this->start = 0.0;
  }

  public function stop()
  {
    $this->end = $this->get_microtime();
  }

  public function getElapsedTime( $decimal = 3 )
  {
    return round(($this->end - $this->start), $decimal );
  }

}

