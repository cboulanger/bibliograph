<?php

namespace app\modules\zotero\controllers;

use app\controllers\AppController;
use app\lib\jsonrpc\Client;
use app\models\Folder;
use app\modules\zotero\Datasource;
use app\modules\zotero\models\Collection;
use lib\channel\MessageEvent;
use lib\controllers\ITreeController;
use Yii;

class CollectionController
  extends Controller
  implements ITreeController
{
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
   * @param string $datasourceName
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array containing the keys 'nodeCount', 'transactionId'
   *   and (optionally) 'statusText'.
   */
  function actionNodeCount(string $datasourceName, array $options = null)
  {
    $nodeCount = $this->nodeCount($datasourceName);
    return array(
      'nodeCount' => $nodeCount,
      'transactionId' => 0,
      'statusText' => ""
    );
  }

  /**
   * @param string $datasourceName
   * @return int
   * @throws \lib\exceptions\UserErrorException
   */
  protected function nodeCount(string $datasourceName) {
    $api = $this->getZoteroApi($datasourceName);
    $response = $api->collections()->limit(1)->send();
    return (int) $response->getHeaders()['Total-Results'][0];
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
   * Returns all nodes of a tree in a given datasource. This won't work for
   * Zotero because at most 100 nodes will be sent by the server at once.
   * @param string $datasourceNameName
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * //return { nodeData : [], statusText: [] }.
   */
  function actionLoad($datasourceNameName, $options = null)
  {
    $nodeDataList = $this->nodeDataList($datasourceNameName, $options['parentCollection'] ?? null);
    return [
      'nodeData' => $nodeDataList,
      'statusText' => $this->nodeCount($datasourceNameName) . " Folders total"
    ];
  }

  /**
   * Side effect: messages requesting the loading of subfolders!
   * @param $datasourceName
   * @param null $parentKey
   * @return array
   * @throws \lib\exceptions\UserErrorException
   */
  protected function nodeDataList($datasourceName, $parentKey=null) {
    $api = $this->getZoteroApi($datasourceName);
    if ($parentKey) {
      $response = $api
        ->collections($parentKey)
        ->subCollections()
        ->limit(100)
        ->send();
    } else {
      $response = $api
        ->collections()
        ->top()
        ->limit(100)
        ->send();
    }
    $collections = $response->getBody();
    $nodeDataList = [];
    foreach ($collections as $collectionData) {
      $data = $this->nodeData($collectionData, $datasourceName);
      $nodeDataList[] = $data;
      if ($collectionData['meta']['numCollections'] > 0) {
        // dynamically add subfolders
        $parentKey = $collectionData['data']['key'];
        $this->requestSubfolders($datasourceName, $parentKey);
      }
    }
    return $nodeDataList;
  }

  /**
   * Converts a
   * @param $key
   * @return int
   */
  protected function getNodeIdFromKey($key) {
    if (!$key) return 0;
    $index = Yii::$app->cache->get("zotero-key-index");
    if (! $index) {
      $index = 0;
    }
    if (! Yii::$app->cache->get("zotero-index-$key")) {
      Yii::$app->cache->set("zotero-index-$key", ++$index);
      Yii::$app->cache->set("zotero-key-index", $index);
    }
    return Yii::$app->cache->get("zotero-index-$key");
  }

  /**
   * @param array $data The data returned from the Zotero server
   * @param string $datasourceName
   * @return array
   */
  protected function nodeData($data, $datasourceName) {
    return [
      'isBranch'        => true,
      'label'           => $data['data']['name'],
      'bOpened'         => false,
      'icon'            => null,
      'iconSelected'    => null,
      'bHideOpenClose'  => ($data['meta']['numCollections'] === 0),
      'columnData'      => [ null, $data['meta']['numItems'] ? $data['meta']['numItems'] : null ],
      'data'            => [
        'type'            => "folder",
        'id'              => $this->getNodeIdFromKey($data['data']['key']),
        'parentId'        => $this->getNodeIdFromKey($data['data']['parentCollection']),
        'query'           => null,
        'public'          => true,
        'owner'           => null,
        'description'     => null,
        'datasource'      => $datasourceName,
        'childCount'      => $data['meta']['numCollections'],
        'referenceCount'  => $data['meta']['numItems'],
        'markedDeleted'   => false
      ]
    ];
  }

  /**
   * @param $datasourceName
   * @param $parentKey
   */
  protected function requestSubfolders($datasourceName, $parentKey) {
    Client::execute("zotero.collection.add-subfolders", [$datasourceName, $parentKey]);
  }

  /**
   * @param $datasourceName
   * @param $parentKey
   * @return string
   * @throws \lib\exceptions\UserErrorException
   */
  public function actionAddSubfolders($datasourceName, $parentKey) {
    $nodeDataList = $this->nodeDataList($datasourceName, $parentKey);
    $this->dispatchClientMessage(Folder::MESSAGE_CLIENT_ADD,[
        'datasource'  => $datasourceName,
        'modelType'   => "folder",
        'nodeData'    => $nodeDataList,
        'transactionId' => 0
      ]);
    return "Returned data for " . count($nodeDataList) . " subfolder(s)";
  }
}
