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
          array( 'label'  => Yii::t('app',"Plugin is not installed"), 'value' => "" ),
          array( 'label'  => Yii::t('app',"Install plugin"), 'value' => "install" )
        );
      }
      else
      {
        if ( $manager->isActive( $namedId ) )
        {
          $options = array(
            array( 'label'  => Yii::t('app',"Plugin is active"), 'value' => "" ),
            array( 'label'  => Yii::t('app',"Deactivate plugin"), 'value' => "deactivate" ),
            array( 'label'  => Yii::t('app',"Uninstall plugin"), 'value' => "uninstall" ),
            array( 'label'  => Yii::t('app',"Reinstall plugin"), 'value' => "reinstall" )
          );
        }
        else
        {
          $options = array(
            array( 'label'  => Yii::t('app',"Plugin is deactivated"), 'value' => "" ),
            array( 'label'  => Yii::t('app',"Activate plugin"), 'value' => "activate" ),
            array( 'label'  => Yii::t('app',"Uninstall plugin"), 'value' => "uninstall" ),
            array( 'label'  => Yii::t('app',"Reinstall plugin"), 'value' => "reinstall" )
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

    return \lib\dialog\Form::create(
      "<h3>" . Yii::t('app',"Please configure the plugins") . "</h3>",
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
            $msg = Yii::t('app',"Installed plugin '%s'",$plugin->getName());
          }
          catch( qcl_application_plugin_Exception $e )
          {
            $msg = Yii::t('app',"Installation of plugin '%s' failed: %s", $plugin->getName(), $e->getMessage());
            $this->getLogger()->log($msg, QCL_LOG_PLUGIN );
          }
          break;

        case "activate":
          $this->getLogger()->log(sprintf(
            "Activating plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          $manager->setPluginActive( $namedId, true );
          $msg = Yii::t('app',"Activated plugin '%s'. You might have to reload the application.",$plugin->getName());
          break;

        case "deactivate":
          $this->getLogger()->log(sprintf(
            "Deactivating plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          $manager->setPluginActive( $namedId, false );
          $msg = Yii::t('app',"Deactivated plugin '%s'. You might have to reload the application.",$plugin->getName());
          break;

        case "reinstall":
          $this->getLogger()->log(sprintf(
            "Reinstalling plugin '%s'", $plugin->getName()
          ), QCL_LOG_PLUGIN );
          try
          {
            $installMsg = $plugin->reinstall();
            $manager->update( $namedId, $plugin );
            $msg = Yii::t('app',"Reinstalled plugin '%s'",$plugin->getName());
          }
          catch( qcl_application_plugin_Exception $e )
          {
            $msg = Yii::t('app',
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
            $msg = Yii::t('app',
              "Uninstalled plugin '%s'",
              $plugin->getName()
            );
          }
          catch( qcl_application_plugin_Exception $e )
          {
            $msg = Yii::t('app',
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
      return \lib\dialog\Alert::create( implode("<br/>", $messages ) );
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
