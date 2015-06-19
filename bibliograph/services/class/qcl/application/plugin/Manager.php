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

qcl_import("qcl_application_plugin_RegistryModel");

/**
 * Manages the installed plugins
 */
class qcl_application_plugin_Manager 
  extends qcl_core_Object
{
  
	/**
	 * Return singleton instance of this class
	 * @return qcl_application_plugin_Manager
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
   * Returns the path to the directory that contains the plugins
   * @return string
   */
  public function getPluginPath()
  {
    return $this->getApplication()->pluginPath();
  }

  /**
   * Given a named id, return the class path of the plugin
   * @param string $namedId
   * @return string
   */
  public function getPluginClassPath( $namedId )
  {
    return $this->getApplication()->pluginPath() . "/$namedId/services/class";
  }

  /**
   * Returns an array of strings, each being the top namespace and identifier of
   * a plugin in the plugin directory which is visible to the application.
   * @return array
   */
  public function getPluginList()
  {
    static $plugin_list = null;
    if ( $plugin_list === null )
    {
      $plugin_list = array();
      $plugin_path = $this->getPluginPath();

      // scan plugin directory
      foreach ( scandir( $plugin_path ) as $namedId )
      {
        if ( $namedId[0] == "." ) continue;

        // instantiate plugin setup class
        $plugin = $this->getSetupInstance( $namedId );

        // if there is no valid plugin or it is not meant to be visible, skip
        if ( ! $plugin or ! $plugin->isVisible() ) continue;

        $plugin_list[] = $namedId;
      }
    }
    return $plugin_list;
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

  /**
   * Loads the plugin setup class, instantiates the class and returns the instance.
   * Returns boolean false if the class file doesn't exist
   * @param string $namedId The named id of the plugin
   * @return qcl_application_plugin_AbstractPlugin|false
   */
  public function getSetupInstance( $namedId )
  {
    try
    {
      $class  = "{$namedId}_Plugin";
      qcl_import( $class );
      return new $class();
    }
    catch( qcl_FileNotFoundException $e )
    {
      return false;
    }
  }

  /**
   * Registers a plugin with a given id
   * @param string $namedId
   * @param qcl_application_plugin_AbstractPlugin $plugin
   * @return void
   */
  public function register( $namedId, qcl_application_plugin_AbstractPlugin $plugin )
  {
    $this->getRegistryModel()->create( $namedId, array(
      'name'        => $plugin->getName(),
      'description' => $plugin->getDescription(),
      'data'        => $plugin->getData(),
      'active'      => true
    ));
  }

  /**
   * Updates a plugin with a given id
   * @param string $namedId
   * @param qcl_application_plugin_AbstractPlugin $plugin
   * @return void
   */
  public function update( $namedId, qcl_application_plugin_AbstractPlugin $plugin )
  {
    $registryModel = $this->getRegistryModel();
    $registryModel->load($namedId);
    $registryModel->set( array(
      'description' => $plugin->getDescription(),
      'data'        => $plugin->getData(),
      'active'      => true
    ) );
    $registryModel->save();
  }

  /**
   * Unregisters a plugin with a given id
   * @param string $namedId
   * @return void
   */
  public function unregister( $namedId )
  {
    $registryModel = $this->getRegistryModel();
    $registryModel->load( $namedId );
    $registryModel->delete();
  }

  /**
   * Sets the 'active' state of the plugin
   * @param string $namedId
   * @param bool $value
   * @return void
   */
  public function setPluginActive( $namedId, $value )
  {
    $registryModel = $this->getRegistryModel();
    $registryModel->load( $namedId );
    $registryModel->set('active', $value )->save();
  }

  /**
   * Returns an array of plugin data of the active plugins
   * @return array
   */
  public function getPluginData()
  {
    $data = array();
    $registryModel = $this->getRegistryModel();
    $registryModel->findWhere( array( 'active' => true ) );
    while( $registryModel->loadNext() )
    {
      $pluginData = $registryModel->get("data");
      $pluginData['name']      = $registryModel->get("name");
      $pluginData['namespace'] = $registryModel->getNamedId();
      array_push($data, $pluginData);
    }
    return $data;
  }
}