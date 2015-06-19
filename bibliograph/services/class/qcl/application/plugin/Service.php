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

qcl_import("qcl_data_controller_Controller");
qcl_import("qcl_application_plugin_Manager");
qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_Form");

/**
 * The plugin Service
 * @author bibliograph
 */
class qcl_application_plugin_Service
  extends qcl_data_controller_Controller
{

  /**
   * Returns the plugin manager instance
   * @return qcl_application_plugin_Manager
   */
  protected function getPluginManager()
  {
    return qcl_application_plugin_Manager::getInstance();
  }
  
  /**
   * Creates form to install or uninstall plugins
   * @return qcl_ui_dialog_Form
   */
  public function method_manage()
  {

    $formData = array();
    $manager = $this->getPluginManager();
    //
    foreach ( $manager->getPluginList() as $namedId )
    {

      $plugin = $manager->getSetupInstance( $namedId );
      $label  = $plugin->getName();// . "<br/>" . $plugin->getDescription();

      if ( ! $manager->isInstalled( $namedId ) )
      {
        $options = array(
          array( 'label'  => $this->tr("Plugin is not installed"), 'value' => "" ),
          array( 'label'  => $this->tr("Install plugin"), 'value' => "install" )
        );
      }
      else
      {
        if ( $manager->isActive( $namedId ) )
        {
          $options = array(
            array( 'label'  => $this->tr("Plugin is active"), 'value' => "" ),
            array( 'label'  => $this->tr("Deactivate plugin"), 'value' => "deactivate" ),
            array( 'label'  => $this->tr("Uninstall plugin"), 'value' => "uninstall" ),
            array( 'label'  => $this->tr("Reinstall plugin"), 'value' => "reinstall" )
          );
        }
        else
        {
          $options = array(
            array( 'label'  => $this->tr("Plugin is deactivated"), 'value' => "" ),
            array( 'label'  => $this->tr("Activate plugin"), 'value' => "activate" ),
            array( 'label'  => $this->tr("Uninstall plugin"), 'value' => "uninstall" ),
            array( 'label'  => $this->tr("Reinstall plugin"), 'value' => "reinstall" )
          );
        }
      }

      $formData[$namedId] = array(
        'type'    => "selectbox",
        'width'   => 300,
        'options' => $options,
        'label'   => $label,
        'value'   => $namedId
      );
    }

    return new qcl_ui_dialog_Form(
      "<h3>" . $this->tr("Please configure the plugins") . "</h3>",
      $formData, true,
      $this->serviceName(), "handlePluginForm"
    );
  }

  /**
   * Service method to handle the user action in the plugin dialog
   */
  public function method_handlePluginForm( $data )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }

    $messages = array();

    foreach( $data as $namedId => $action )
    {
      $manager = $this->getPluginManager();
      $plugin = $manager->getSetupInstance( $namedId );
      $msg = "";
      $installMsg = "";

      switch ( $action )
      {
        case "install":
          $this->getLogger()->log(sprintf(
            "Installing plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          try
          {
            $installMsg = $plugin->install();
            $manager->register( $namedId, $plugin );
            $msg = $this->tr("Installed plugin '%s'",$plugin->getName());
          }
          catch( qcl_application_plugin_Exception $e )
          {
            $msg = $this->tr("Installation of plugin '%s' failed: %s", $plugin->getName(), $e->getMessage());
            $this->getLogger()->log($msg, QCL_LOG_PLUGIN );
          }
          break;

        case "activate":
          $this->getLogger()->log(sprintf(
            "Activating plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          $manager->setPluginActive( $namedId, true );
          $msg = $this->tr("Activated plugin '%s'. You might have to reload the application.",$plugin->getName());
          break;

        case "deactivate":
          $this->getLogger()->log(sprintf(
            "Deactivating plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          $manager->setPluginActive( $namedId, false );
          $msg = $this->tr("Deactivated plugin '%s'. You might have to reload the application.",$plugin->getName());
          break;

        case "reinstall":
          $this->getLogger()->log(sprintf(
            "Reinstalling plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          try
          {
            $installMsg = $plugin->reinstall();
            $manager->update( $namedId, $plugin );
            $msg = $this->tr("Reinstalled plugin '%s'",$plugin->getName());
          }
          catch( qcl_application_plugin_Exception $e )
          {
            $msg = $this->tr(
              "Re-Installation of plugin '%s' failed: %s",
              $plugin->getName(), $e->getMessage()
            );
            $this->getLogger()->log($msg, QCL_LOG_PLUGIN );
          }
          break;

        case "uninstall":
          $this->getLogger()->log(sprintf(
            "Uninstalling plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          try
          {
            $installMsg = $plugin->uninstall();
            $manager->unregister( $namedId );
            $msg = $this->tr(
              "Uninstalled plugin '%s'",
              $plugin->getName()
            );
          }
          catch( qcl_application_plugin_Exception $e )
          {
            $msg = $this->tr(
              "Uninstallation of plugin '%s' failed: %s",
              $plugin->getName(), $e->getMessage()
            );
            $this->getLogger()->log($msg, QCL_LOG_PLUGIN );
          }
          break;
      }

      if ( $msg or $installMsg )
      {
        if ( $installMsg )
        {
          $msg .= ": " . $installMsg;
        }
        $messages[] = $msg;
      }
    }
    if ( count( $messages) )
    {
      return new qcl_ui_dialog_Alert( implode("<br/>", $messages ) );
    }
    return "OK";
  }

  /**
   * Service returning an array of plugin data of the active plugins
   * @return array
   */
  public function method_getPluginData()
  {
    return $this->getPluginManager()->getPluginData();
  }

}
