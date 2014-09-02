<?php

qcl_import("bibliograph_model_AbstractDatasourceModel");


class bookends_DatasourceModel
  extends bibliograph_model_AbstractDatasourceModel
{

  /**
   * overridden. initializes all models that belong to this datasource
   */
  function initializeModels()
  {
    $controller = $this->getController();
    if ( $this->getHost() )
    {
      $this->recordModel = new bibliograph_plugins_bookends_Model( $controller, $this );

      if ( ! $this->recordModel->isConnected )
      {
        throw new LogicException( $this->recordModel->getError() );
      }
    }
  }

  public function getTableModelType()
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Returns a list of fields that should be disabled in a form
   * @return array
   */
  function unusedFields()
  {
    return array("resourcepath","prefix");
  }

  /**
   * Creates a new Bookends datasource
   * @return bool
   * @param string $datasource datasource name
   * @param array  $options    connection data etc.
   */
  function create ( $datasource, $options = array()  )
  {
     /*
     * check datasource name
     */
    if ( ! $this->_checkCreate($datasource) ) return false;

    /*
     * create entry
     */
    $this->insert(array(
      "namedId"      => $datasource,
      "active"       => isset($options['active']) ? $options['active'] : 1,
      "readonly"     => isset($options['readonly']) ? $options['readonly'] : 0,
      "native"       => false,
      "name"         => either($options['name'], $datasource),
      "schema"       => "bookends",
      "type"         => "http",
      "host"         => either($options['host'], "" ),
      "port"         => either($options['port'], "" ),
      "database"     => either($options['database'], "" ),
      "username"     => either($options['username'], "" ),
      "password"     => either($options['password'], "" ),
      "encoding"     => either($options['encoding'],"utf8"),
      "description"  => (string) $options['description'],
      "owner"        => either($options['owner'],""),
      "hidden"       => isset($options['hidden']) ? $options['hidden'] : 0,
    ));

   return true;
  }
   /*
   * Models this datasource doesn't have
   */
  function getNoteModel() { return null; }
  function getTagModel() { return null; }
  function getAttachmentModel() { return null; }

  /**
   * Returns the sync data model of this datasource,
   * which is only created on demand
   * @return bibliograph_model_sync_Model
   */
  function getSyncDataModel()
  {
    if ( ! $this->_syncDataModel )
    {
      $this->_syncDataModel =& new bibliograph_plugins_bookends_Synchronizer( $this );
    }
    return $this->_syncDataModel;
  }


}

