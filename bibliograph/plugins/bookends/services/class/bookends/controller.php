<?php

/*
 * dependencies
 */


/**
 * Service controller class for bookends plugin
 */
class class_bibliograph_plugins_bookends_controller extends bibliograph_controller
{

  /**
   * Persistent synchronizer object
   * @var bibliograph_plugins_bookends_Synchronizer
   */
  var $synchronizer;

  /**
   * Initiates the sync from the server side.
   * @todo permissions!!!
   *
   * @param unknown_type $params
   * @return unknown
   */
  function method_initiateSync($params)
  {
    list( $source, $result ) = $params;

    /*
     * Models used
     */
    $config   =& $this->getConfigModel();
    $client   =& new qcl_application_Client($this);
    $dsModel  =& new bibliograph_model_datasource_Datasource( $this );

    /*
     * source datasource
     */
    $dsModel->findByNamedId($source);
    $title = $dsModel->getName();

    /*
     * If this method is called for the first time, return form
     */
    if ( ! $result )
    {
      $formData = array();

      /*
       * Bookends target database
       */
      $bookends_ds = $dsModel->findBy("schema","bookends", "name", array('name','namedId') );
      $formData['target'] = array( 'label' => "Bookends Datasource" );
      foreach ( $bookends_ds as $row )
      {
        $formData['target']['options'][] = array( 'label' => $row['name'], 'value' => $row['namedId'], 'icon' => null );
      }
      $formData['target']['value'] = either ( $config->get("bibliograph.plugins.bookends.lastDatasource") , $bookends_ds[0]['namedId']) ;

      /*
       * display form on the client
       */
      return $client->presentForm($this->tr("Please choose the datasources to synchronize '$title' with ..."),$formData );

    }

    /*
     * get result
     */

    $target = $result->target->value;

    /*
     * remember the ds to sync to
     */
    $config->set("bibliograph.plugins.bookends.lastDatasource",$target);

    /*
     * dispatch message that will trigger the start of the synchronization
     */
    $this->dispatchMessage("bibliograph.plugins.bookends.commands.synchronize",array(
      'source' => $source,
      'target' => $target
    ));

    /*
     * debug filter
     */
    $logger =& $this->getLogger();
    if ( ! $logger->isRegistered("sync") )
    {
      $logger->registerFilter("sync");
    }
    $logger->setFilterEnabled("sync",false);

    /*
     * return to client with message data
     */
    return $this->response();

  }

  /**
   * Prepares and calls synch process between Bibliograph datasource
   * and Bookends database
   *
   * @param string $params [3] If set, confirmation messages are bypassed
   * @return array
   */
  function method_synchronize( $params )
  {
    /*
     * parameters
     */
    list( $id, $continue, $data, $confirm ) = $params;

    /*
     * persistent synchronizer object
     */
    $sync =& new bibliograph_plugins_bookends_Synchronizer($this,$id);


    /*
     * end process?
     */
    if ( ! $continue )
    {
      if ( $sync->completed )
      {
        $sync->delete();
        $this->info("Synchronization completed.");
        return $this->response();
      }
      else
      {
        $sync->delete();
        $this->info("Synchronization aborted.");
        return $this->response();
      }
    }

    /*
     * bibliograph datasource
     */
    $source = $data->source;
    if ( ! $source )
    {
      return $this->abort("No source datasource!");
    }

    /*
     * Check for Bookends database
     * @todo unhardcode this!
     */
    $target = $data->target;
    if ( ! $target )
    {
      return $this->abort("No target datasource!");
    }

    /*
     * Check if we have already sync'd the datasources before,
     * if not, confirm. Skip this step
     */
    if ( ! $confirm and ! $sync->syncConfirmed )
    {
      if ( ! $sync->syncDataExists( $source, $target ) )
      {
        return $this->confirmRemote( $this->tr(
            "Datasources '%s' and '%s' have never been synchronized before. Are you sure you want to proceed?",
            $source, $target
        ) );
      }
    }
    else
    {
      $sync->syncConfirmed = true;
    }

    /*
     * initialize persistent synchronizer object
     */
    if ( $sync->isNew() )
    {
      /*
       * connect to bookends database
       */
      $this->info("Connecting to Bookends server ...");
      $bookendsModel =& $this->getRecordModel( $target );
      if ( ! $bookendsModel->isConnected )
      {
        return $this->abort($bookendsModel->getError());
      }
      $database = $bookendsModel->getDatabase();
      $this->info("Connection to Bookends database '$database' successful.");

      /*
       * configure the synchronizer
       */
      $recordModel =& $this->getRecordModel($source);
      $sync->configure(
        $source,
        $target,
        $recordModel,
        $database
      );

      if ( $sync->getError() )
      {
        return $this->abort( $sync->getError() );
      }

      $this->info("Starting sync between Bibliograph (Datasource '$source', {$sync->recordCount} records) and Bookends (Database '$database') ..." );

    }
    else
    {
      //$this->info("Resuming sync between Bibliograph($source, {$sync->recordCount} records) and Bookends({$sync->database}) ..." );
    }

    $sync->_controller =& $this;

    /*
     * run sync action
     */
    $result = $sync->run();

    /*
     * error
     */
    if ( $result === false )
    {
      return $this->abort( $sync->getError() );
    }

    /*
     * sync is complete, end process
     */
    elseif ( $result === true )
    {
      $this->dispatchEvent("endProcess");
    }

    /*
     * sync is still running, display status message
     * on client
     */
    else
    {
      $this->dispatchEvent("displayServerMessage",$result);
    }

    /*
     * return response data
     */
    return $this->response();
  }

  function abort ( $message )
  {
    $this->dispatchEvent("endProcess");
    return $this->alert( $message );
  }


  function method_clearSyncData( $params )
  {
    list($confirm) = $params;
    $configModel =& $this->getConfigModel();

    if ( ! $confirm )
    {
      $datasource = $configModel->get("bibliograph.plugins.bookends.lastDatasource");
      return $this->confirmRemote( $this->tr("Do you really want to delete clear all synchronization for '%s'?", $datasource ) );
    }
    return $this->alert("Not implemented.");
  }

  /**
   * Copies a field from bibliograph to bookends
   */
  function method_copyField($params)
  {
    /*
     * parameters
     */
    list ( $source, $target, $field, $reftype ) = $params;

    /*
     * models
     */
    $sourceModel =& $this->getDatasourceModel($source);
    $srcRecModel =& $sourceModel->getRecordModel();
    $targetModel =& $this->getDatasourceModel($target);
    $tgtRecModel =& $targetModel->getRecordModel();
    //$syncDataMdl =& $this->getSyncDataModel($source);

    /*
     * find record(s)
     */
    $targetField = $tgtRecModel->getColumnName($field);
    $where = "publisher IS NOT NULL and $targetField IS NULL AND user18 IS NOT NULL";

    $tgtRecModel->findWhere( $where, "" , 10 );

    if ( $tgtRecModel->foundNothing() )
    {
      return $this->alert("No records of type $reftype matching '$where' can be found.");
    }

    do
    {
      $targetId = $tgtRecModel->getId();
      /*
      $syncDataMdl->findWhere("tgt_id = $targetId");
      if ( $syncDataMdl->foundNothing() )
      {
        $this->warn("Recorrd #$targetId, datasource $target has not been synchronized. Skipping...");
      }*/

      //else
      if ( true )

      {
        $sourceId = $tgtRecModel->getProperty("bibliograph-id");
        //$sourceId = $syncDataMdl->getSourceId();
        if ( ! $sourceId )
        {
          $this->warn("Invalid source id for source id #$targetId");
        }
        else
        {
          $srcRecModel->load($sourceId);
          $value = $srcRecModel->getProperty($field);
          $this->info("Transferring data for source #$sourceId, target #$targetId");
          $tgtRecModel->update(array(
            'id' => $targetId,
             $field => $value
          ));

        }
      }
    }
    while ( $tgtRecModel->nextRecord() );

    /*
     * enable automatic resubmitting / reposting of last request
     */
    $this->dispatchMessage("qcl.commands.repeatLastRequest" );

    return $this->response();
  }

}

