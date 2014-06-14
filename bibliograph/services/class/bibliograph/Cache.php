<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_file_PersistentObject" );

class bibliograph_Cache
  extends qcl_data_file_PersistentObject
{
  public $map = array();

  /**
   * Returns singleton instance
   * @return bibliograph_Cache
   */
  static public function getInstance()
  {
    /*
     * create instance
     */
    $instance = qcl_getInstance( __CLASS__ );

    /*
     * inform about cache file
     */
    if($instance->isNew())
    {
      $msg = "Created application data cache at " .
        $instance->getPersistenceBehavior()->getResourcePath();
      qcl_log_Logger::getInstance()->log($msg, BIBLIOGRAPH_LOG_APPLICATION);
      $instance->isNew = false; //hack
    }

    return $instance;
  }

  /**
   * Sets a persisted value
   * @param $key
   * @param $value
   */
  public function setValue( $key, $value )
  {
    $this->map[$key] = $value;
  }

  /**
   * Returns a persisted value
   * @param $key
   * @return mixed
   */
  public function getValue( $key )
  {
    return $this->map[$key];
  }
}
?>