<?php

namespace app\modules\zotero\controllers;

use app\controllers\AppController;
use app\modules\zotero\models\Collection;
use lib\controllers\ITreeController;

class CollectionController extends AppController implements ITreeController {
   /*
    ---------------------------------------------------------------------------
       STATIC PROPERTIES & METHODS
    ---------------------------------------------------------------------------
    */

  /**
   * The main model type of this controller
   */
  static $modelType = "folder";

  /**
   * The class that is used for the folder model
   * @var string
   */
  static $modelClass = Collection::class;


   /*
    ---------------------------------------------------------------------------
       INTERFACE ITreeController
    ---------------------------------------------------------------------------
    */

  /**
   * Returns the number of nodes in a given datasource
   *
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array containing the keys 'nodeCount', 'transactionId'
   *   and (optionally) 'statusText'.
   */
  function actionNodeCount(string $datasource, array $options = null)
  {

    return array(
      'nodeCount' => $nodeCount,
      'transactionId' => 0,
      'statusText' => ""
    );
  }

  /**
   * Returns the number of children of a node with the given id
   * in the given datasource.
   *
   * @param $datasource
   * @param $nodeId
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array
   */
  function actionChildCount($datasource, $nodeId, $options = null)
  {
    throw new \BadMethodCallException("Not implemented");
  }

  /**
   * Returns all nodes of a tree in a given datasource
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * //return { nodeData : [], statusText: [] }.
   */
  function actionLoad($datasource, $options = null)
  {

    //$this->addLostAndFound($orderedNodeData);
    return [
      'nodeData' => $orderedNodeData,
      'statusText' => count($orderedNodeData) . " Folders loaded."
    ];
  }
}
