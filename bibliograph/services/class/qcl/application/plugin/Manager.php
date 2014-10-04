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


/**
 * Manages the installed plugins
 */
class qcl_application_plugin_Manager 
  extends qcl_core_Object
{
  
	/**
	 * Return singleton instance of this class
	 * @return qcl_locale_Manager
	 */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }
  
  /**
   * Returns a the instance of the registry model
   * @return qcl_application_plugin_RegistryModel
   */
  protected function getRegistryModel()
  {
    return qcl_application_plugin_RegistryModel::getInstance();
  }
  
  /**
   * Returns true if plugin is installed
   * @param string $name
   * @return bool
   */
  public function isInstalled( $name )
  {
    try
    {
      $this->getRegistryModel()->load( $name );
      return true;
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      return false;
    }
  }
  
  /**
   * Returns true if plugin is installed and activated
   * @param string $name
   * @return bool
   */
  public function isActive( $name )
  {
    try
    {
      return $this->getRegistryModel()->load( $name )->getActive();
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      return false;
    }
  }
}