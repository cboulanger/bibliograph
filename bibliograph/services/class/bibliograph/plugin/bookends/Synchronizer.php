<?php
/*
 * dependencies
 */

throw new qcl_core_NotImplementedException("bibliograph_plugins_bookends_Synchronizer");


//require_once "qcl/persistence/db/TaskRunner.php";
//
///*
// * Class to synchronize between a bibliograph database and
// * a bookends database
// */
//class bibliograph_plugins_bookends_Synchronizer extends qcl_persistence_db_TaskRunner
//{
//  /**
//   * Bibliograph datasource
//   */
//  var $source;
//
//  /**
//   * The target Bookends datasource
//   */
//  var $target;
//
//  /**
//   * Bookends database
//   */
//  var $database;
//
//  /**
//   * Array containing list of record ids and corresponding
//   * modification timestamps
//   * @var array
//   */
//  var $modifiedList = array(
//    array(),
//    array()
//  );
//
//  /**
//   * Counter for modified list
//   * @var int
//   */
//  var $counter = 0;
//
//  /**
//   * Original number of records
//   * @var int
//   */
//  var $recordCount = 0;
//
//  /**
//   * If sync action has been confirmed by the user
//   * @todo integrate into data property
//   * @var bool
//   */
//  var $syncConfirmed = false;
//
//  /**
//   * Storage for various data elements that need
//   * to be persisted
//   * @var array
//   */
//  var $data = array();
//
//  /**
//   * The data model managing the sync info
//   * @var bibliograph_model_sync_Model
//   */
//  var $_syncModel;
//
//  /**
//   * The record model of the bibliograph data
//   * @var bibliograph_model_record_default
//   */
//  var $_srcModel;
//
//  /**
//   * The record model of the bookends data
//   * @var bibliograph_plugins_bookends_Model
//   */
//  var $_tgtModel;
//
//
//  /**
//   * The bookends field storing the bibliograph id
//   */
//  var $beBibliographIdField = "user18";
//
//  /**
//   * Configures the persistent object
//   * @override
//   * @param string $source
//   * @param string $target
//   * @param qcl_data_model_xmlSchema_DbModel $model
//   * @param string $database
//   * @return bool
//   */
//  function configure( $source, $target, $model, $database )
//  {
//    //$this->info("Configuring synchronizer with $source, $target, $database");
//
//    if ( ! $source or ! $target or ! is_a($model, "qcl_data_model_xmlSchema_DbModel" ) or ! $database )
//    {
//      $this->setError("Invalid configuration.");
//      return false;
//    }
//
//    /*
//     * modification list
//     * @todo remove unchanged records right from the start
//     */
//    $modList = $model->getModificationList();
//
//    $this->source       = $source;
//    $this->target       = $target;
//    $this->database     = $database;
//    $this->modifiedList = array(
//      array_keys( $modList ),
//      array_values( $modList )
//    );
//    $this->recordCount  = count($modList);
//    return $this->save();
//  }
//
//  /**
//   * Run the current task
//   * @return mixed
//   */
//  function run()
//  {
//    /*
//     * check configuration
//     */
//    if ( ! $this->source or ! $this->target )
//    {
//      return $this->abort("Invalid source('{$this->source}') or target ('{$this->target}')");
//    }
//
//    /*
//     * controller
//     */
//    $controller =& $this->getController();
//
//    /*
//     * bibliograph model
//     */
//    $this->_srcModel =& $controller->getRecordModel($this->source);
//
//    /*
//     * bookends model
//     */
//    $this->_tgtModel =& $controller->getRecordModel($this->target);
//
//    /*
//     * sync info data model
//     */
//    if ( ! $this->_syncModel )
//    {
//      $this->_syncModel =& $controller->getSyncDataModel($this->source);
//    }
//
//    /*
//     * run task
//     */
//    return parent::run();
//  }
//
//  function syncDataExists($source,$target)
//  {
//    /*
//     * sync info data model
//     */
//    if ( ! $this->_syncModel )
//    {
//      $controller =& $this->getController();
//      $this->_syncModel =& $controller->getSyncDataModel($source);
//    }
//
//    /*
//     * check if sync data exists
//     */
//    return $this->_syncModel->syncDataExists($target);
//  }
//
//  function task_1()
//  {
//    return $this->endTask("<div>Synchronizing Bibliograph ({$this->source}) to Bookends ({$this->database}) ...</div>");
//  }
//
//
//  /**
//   * Synchronize from Bibliograph to Bookends
//   */
//  function task_2()
//  {
//    /*
//     * if we have reached the end of the counter, switch to next task
//     */
//    if ( ! count($this->modifiedList[0]) )
//    {
//      return $this->endTask("Synchronization to Bookends completed.");
//    }
//
//    /*
//     * log progress
//     */
//    $this->counter++;
//    $this->log("Sync '{$this->source}' with '{$this->target}': Record {$this->counter} of {$this->recordCount} ...","sync" );
//
//    /*
//     * use first record of modification list and remove it
//     */
//    $bibliograph_id = (int)    array_shift($this->modifiedList[0]);
//    $modified       = (string) array_shift($this->modifiedList[1]);
//    //$this->log("Id : $bibliograph_id, modified: $modified","sync");
//
//    /*
//     * check for valid bibliograph id.
//     */
//    if ( ! $bibliograph_id )
//    {
//      return $this->abort("Invalid id in record list.");
//    }
//
//    /*
//     * get record data and abort if not found
//     */
//    $this->_srcModel->findById( $bibliograph_id );
//
//    if ( $this->_srcModel->foundNothing() )
//    {
//      $this->warn("Record #$bibliograph_id does not exist in Bibliograph datasource '{$this->source}'.");
//      return $this->getProgressHtml();
//    }
//
//    /*
//     * If record is marked deleted, skip.
//     */
//    if ( $this->_srcModel->isMarkedDeleted() )
//    {
//      return $this->getProgressHtml();
//    }
//
//    /*
//     * flag defaults
//     */
//    $doSync         = false;
//    $createSyncData = false;
//
//    /*
//     * check whether and when this record has been synchronized
//     */
//    $this->_syncModel->findData( $bibliograph_id, "reference", $this->target );
//
//    /*
//     * if no sync data exists for this record or
//     * source record has been modified since the last sync,
//     * create new record in target
//     */
//    if ( $this->_syncModel->foundNothing() )
//    {
//      $createSyncData = true;
//      $doSync         = true;
//      $bookends_id    = null;
//    }
//    else
//    {
//      if ( $this->_syncModel->getProperty("src_modified") != $modified )
//      {
//        /*
//         * the modification timestamp doesn't match, record needs to be updated
//         */
//        $bookends_id = $this->_syncModel->getProperty("tgt_id");
//
//        /*
//         * delete sync record if we do not have a valid target id
//         */
//        if ( ! $bookends_id )
//        {
//          $this->warn("Invalid target id for bibliograph id #$bibliograph_id. Deleting sync record and resyncing...");
//          $this->_syncModel->delete();
//          $doSync = true;
//        }
//
//        /*
//         * record has changed
//         */
//        else
//        {
//          $this->info("Bibliograph record #$bibliograph_id (Bookends record #$bookends_id) has changed ...");
//          $doSync = true;
//        }
//
//      }
//
//    }
//
//    /*
//     * sync record
//     */
//    if ( $doSync )
//    {
//
//      /*
//       * If no bookends id is known,
//       * we have to get the bookends record id
//       * via the citekey
//       */
//      if ( ! $bookends_id )
//      {
//
//        /*
//         * get citekey (remove special characters)
//         */
//        $citekey = $this->_srcModel->getCitekey();
//
//        /*
//         * find citekey
//         */
//        $this->_tgtModel->findByCitekey($citekey);
//
//        if ( $this->_tgtModel->foundNothing() )
//        {
//          /*
//           * Was the record not found or did an error occur?
//           */
//          if ( $this->_tgtModel->foundNoMatches() )
//          {
//
//            /*
//             * try the old citekey (remove non-alphanumeric characters)
//             */
//            $this->_tgtModel->findByCitekey( $this->convertCitekey( $citekey ) );
//
//            if ( $this->_tgtModel->foundNoMatches() )
//            {
//              /*
//               * create a new record in the bookends database
//               */
//              $data = $this->_srcModel->getSharedPropertyValues( $this->_srcModel);
//              $data['bibliograph-id'] = $bibliograph_id;
//              $bookends_id = $this->_tgtModel->create(
//                $this->_srcModel->getReftype(),
//                $citekey,
//                $data
//              );
//
//              if ( $bookends_id )
//              {
//                $doSync = false;
//                $this->info("Created new record #$bookends_id for '$citekey'...");
//              }
//            }
//          }
//          /*
//           * still no id? abort
//           */
//          if ( ! $bookends_id )
//          {
//            return $this->abort($this->_tgtModel->getError());
//          }
//        }
//        else
//        {
//          /*
//           * One or more matching records exist.
//           * If we have more than one record with the
//           * given citekey, use the last
//           */
//          $result = $this->_tgtModel->getResult();
//
//          if ( ( $count = count($result) ) > 1 )
//          {
//            $this->_tgtModel->setRecord( $result[$count-1] );
//          }
//          $bookends_id = $this->_tgtModel->getId();
//          $this->log("'$citekey' exists in Bookends database $database with id #$bookends_id...");
//        }
//      }
//    }
//
//    /*
//     * update Bookends record with Bibliograph record data
//     */
//    if ( $doSync )
//    {
//
//      /*
//       * copy over properties but keep separate ids
//       */
//      $this->_tgtModel->setId($bookends_id);
//      $this->_tgtModel->copySharedProperties( $this->_srcModel );
//      $this->_tgtModel->setId($bookends_id); // was overwritten
//      $this->_tgtModel->setProperty("bibliograph-id", $bibliograph_id);
//
//      /*
//       * convert "annote" fiels
//       */
//      $annote =html2utf8( $this->_srcModel->getAnnote() );
//      $this->_tgtModel->setAnnote($annote);
//
//
//      /*
//       * convert citekey to ASCII - only
//       */
//      $this->_tgtModel->setCitekey( $this->convertCitekey( $this->_srcModel->getCitekey() ) );
//
//      /*
//       * update the record and check response
//       */
//      if ( ! $this->_tgtModel->update() )
//      {
//        if ( $this->_tgtModel->foundNoMatches() )
//        {
//          $this->warn("Invalid target id for bibliograph id #$bibliograph_id.");
//          if ( $this->_syncModel->foundSomething() )
//          {
//            $this->_syncModel->delete();
//          }
//
//          /*
//           * start again with this record
//           */
//          array_unshift( $this->modifiedList[0], $bibliograph_id );
//          array_unshift( $this->modifiedList[1], $modified );
//          $this->counter--;
//          return $this->getProgressHtml();
//        }
//        else
//        {
//          return $this->abort( $this->_tgtModel->getError() );
//        }
//      }
//      else
//      {
//        $this->info("Bookends record #$bookends_id updated.");
//      }
//
//      /*
//       * create or update sync data
//       */
//      if ( $createSyncData )
//      {
//        /*
//         * Create
//         */
//        $this->log("No sync data for record #$bibliograph_id. Creating it...","sync");
//        $this->_syncModel->addData( $bibliograph_id, $modified, "reference", $this->target, $bookends_id );
//      }
//      elseif ( $this->_syncModel->foundSomething() )
//      {
//        /*
//         * update sync data record
//         */
//        $this->_syncModel->updateData( $bibliograph_id, $modified, "reference", $this->target, $bookends_id );
//      }
//
//      $this->log("Updated sync data.","sync");
//
//    }
//
//    /*
//     * return progress bar html
//     */
//    return $this->getProgressHtml();
//
//  }
//
//  /*
//   * Return server message html with a progress bar.
//   * @return string
//   */
//  function getProgressHtml()
//  {
//    $percent = floor(($this->counter/$this->recordCount) * 100);
//    $controller =& $this->getController();
//    $html = $controller->getProgressBarHtml($percent);
//    $html .= "<div>Synchronizing Bibliograph ({$this->source}) to Bookends ({$this->database}) $percent% ({$this->counter}/{$this->recordCount}) ...</div>";
//    return $html;
//  }
//
//  function task_3()
//  {
//    return $this->endTask("<div>Copying new records from Bookends ({$this->database}) to Bibliograph ({$this->source}) ...</div>");
//  }
//
//  /**
//   * Copying new records from Bookends to Bibliograph
//   */
//  function task_4()
//  {
//
//
//    /*
//     * find all records that do not have a bibliograph id in the user18 field
//     */
//    $this->_tgtModel->findWhere("{$this->beBibliographIdField} IS NULL");
//
//    /*
//     * if records have been found, copy over to bibliograph
//     */
//    if ( $this->_tgtModel->foundSomething() )
//    {
//
//      $this->info("Copying new records from Bookends to Bibliograph...");
//
//      do
//      {
//        /*
//         * create new bibliograph record and copy properties
//         */
//        $this->_srcModel->create( $this->_tgtModel->getCiteKey() );
//        $this->_srcModel->copySharedProperties( $this->_tgtModel );
//        $this->_srcModel->save();
//        $src_id = $this->_srcModel->getId();
//        $this->info("Created new bibliograph record #$src_id.");
//
//        /*
//         * update bibliograph id
//         */
//        $tgt_id = $this->_tgtModel->getId();
//        $this->_tgtModel->setProperty("bibliograph-id",$src_id);
//        $this->_tgtModel->save();
//        $this->info("Updated bookends record #$tgt_id with bibliograph id '$src_id'.");
//
//      }
//      while ( $this->_tgtModel->nextRecord() );
//    }
//    else
//    {
//      $this->info("No new records in Bookends.");
//    }
//
//    return $this->endTask("<div>Synchronization completed.</div>");
//  }
//
//  function convertCitekey( $citekey )
//  {
//    $c2 = new String($citekey);
//    return (string) $c2->replace("/[^a-zA-Z0-9]/m","");
//  }
//
//}
//
