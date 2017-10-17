<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("qcl_server_Service");


class class_debug_Service
  extends qcl_server_Service
{
  /**
   * Service method to return the available log message filters
   * @return array Ordered array of associative arrays
   * with keys 'name', 'description', and 'enabled'
   */
  public function method_getLogFilters(){
    return qcl_log_Logger::getInstance()->getFilterData();
  }

  /**
   * Service method to enable a filter
   *
   * @param string $name
   * @param boolean $value
   * @return string "OK"
   */
  public function method_setFilterEnabled( $name, $value){
    $logger = qcl_log_Logger::getInstance();
    $logger->setFilterEnabled( $name, $value);
    $logger->saveFilters();
    return "OK";
  }
  
  /**
   * Record JSONRPC traffic
   */
  public function method_recordJsonRpcTraffic( $value ){
    $this->getApplication()->getConfigModel()->setKeyDefault("debug.recordJsonRpcTraffic", $value);
    return "OK";
  }
}
