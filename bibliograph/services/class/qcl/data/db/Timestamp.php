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

/*
 * Timestamp class
 * @todo: needs to be formatted according to the sql backend
 */
class qcl_data_db_Timestamp
  extends DateTime
{
  public function __toString()
  {
    return $this->format("Y-m-d H:i:s");
  }
  
  /**
   * Overridden to make PHP 5.2-compatible
   * @see DateTime::diff()
   */
  public function diff( $datetime, $absolute=false )
  {
  	return date_diff( $this, $datetime );
  }
  
  /**
   * Overridden to make PHP 5.2-compatible
   */  
  public function getTimestamp() 
  {
     return method_exists('DateTime', 'getTimestamp') ? 
           parent::getTimestamp() : $this->format('U');
  }
  
}
