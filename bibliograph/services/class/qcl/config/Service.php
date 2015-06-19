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

qcl_import( "qcl_data_controller_Controller" );
qcl_import( "qcl_config_LoadResult" );

/**
 * Service class providing methods to get or set configuration
 * values
 */
class qcl_config_Service
  extends qcl_data_controller_Controller
{

  //-------------------------------------------------------------
  // API methods
  //-------------------------------------------------------------

 /**
  * Service method to load config data
  * @param string|null $filter Filter
  * @return qcl_config_LoadResult
  */
 function method_load( $filter=null )
  {
    $configModel = $this->getApplication()->getConfigModel();
    $configMap   = $configModel->getAccessibleKeys( $filter );
    $result = new qcl_config_LoadResult;
    $result->set( $configMap );
    return $result;
  }

  /**
   * Service method to set a config value
   * @param string $key Key
   * @param mixed $value Value
   * @throws JsonRpcException
   * @return bool
   */
  function method_set( $key, $value )
  {
    $configModel = $this->getApplication()->getConfigModel();

    /*
     * check key
     */
    if ( ! $configModel->keyExists( $key ) )
    {
      throw new JsonRpcException("Configuration key '$key' does not exist");
    }
    if ( ! $configModel->valueIsEditable( $key ) )
    {
      throw new JsonRpcException("The value of configuration key '$key' is not editable");
    }

    /*
     * if value is customizable, set the user variant of the key
     */
    if ( $configModel->valueIsCustomizable( $key ) )
    {
      $this->requirePermission("config.value.edit");
      $configModel->setKey( $key, $value );
    }

    /*
     * else, you need special permission to edit the default
     */
    else
    {
      $this->requirePermission("config.default.edit");
      $configModel->setKeyDefault( $key, $value );
    }

    /*
     * result
     */
    return "OK";
  }


  /**
   * export to xml
   */
  function exportXML()
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
    /*
     * models

    $configModel = $this->getConfigModel();


    $path = "../var/tmp/config.xml";
    unlink($path);

    qcl_import("qcl_data_xml_SimpleXMLElement");
    $xmlModel = qcl_data_xml_SimpleXMLElement::createFromFile($path);
    $xmlModel->load($path);
    $doc = $xmlModel->getDocument();

    $keysNode = $doc->addChild("keys");
    foreach( $configModel->getAll() as $record )
    {
      $keyNode = $keysNode->addChild("key");
      foreach( $record as $key => $value )
      {
        if( $key=="id" )
        {
          continue;
        }
        elseif ( $key == "namedId" )
        {
          $keyNode->addAttribute("name",$record['namedId']);
        }
        elseif ( $key != "value" && $value )
        {
          $keyNode->addAttribute($key,$value);
        }
      }
      $keyNode->setCDATA( htmlentities($record['value']) );
    }
    $this->Info($doc->asXml());
    */
  }

  function deleteLogFile()
  {
    unlink( QCL_LOG_FILE );
    touch ( QCL_LOG_FILE );
    return $this->alert($this->tr("Log file deleted."));
  }

}
