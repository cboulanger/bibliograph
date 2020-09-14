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
use function foo\func;

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
   * @param string $parentKey
   * @return int
   * @throws \lib\exceptions\UserErrorException
   */
  protected function nodeCount(string $datasourceName, $parentKey=null) {
    $api = $this->getZoteroApi($datasourceName);
    $response = $api->collections($parentKey)->limit(1)->send();
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
    $nodeDataList = $this->nodeDataList($datasourceNameName);
    return [
      'nodeData' => $nodeDataList,
      'statusText' => count($nodeDataList) . " Folders",
      'editable' => false
    ];
  }

  /**
   * Given a datasource name and parent key, returns an array of Tree nodes
   * conformant to the SimpleTreeDataMaodel. The data is cached and reloaded
   * only if the version changed.
   * @param $datasourceName
   * @return array
   * @throws \lib\exceptions\UserErrorException
   */
  protected function nodeDataList($datasourceName) {
    $versions =
      $this->getZoteroApi($datasourceName)
      ->collections()
      ->versions()
      ->send()
      ->getBody();

    $cachedVersions = Yii::$app->cache->get("zotero-$datasourceName-versions");
    $cachedNodeDataList = Yii::$app->cache->get("zotero-$datasourceName-nodes");
    $cachedCollections = Yii::$app->cache->get("zotero-$datasourceName-collections");

    // if versions changed,invalidate cache
    $countDiff = count(array_diff_assoc($versions, $cachedVersions));
    if (is_array($cachedVersions) and $countDiff > 0) {
      Yii::debug("Invalidating cache since $countDiff collections have changed.");
      $cachedVersions = null;
      $cachedCollections = null;
      $cachedNodeDataList = null;
    }

    if (is_array($cachedNodeDataList)) {
      //return $cachedNodeDataList;
    }

    $nodeCount = count($versions);
    if (!is_array($cachedCollections)) {
      $collections = [];
      $count = 0;
      do {
        $newCollections =
          $this->getZoteroApi($datasourceName)
            ->collections()
            ->start($count)
            ->limit(100)
            ->send()
            ->getBody();
        $collections = array_merge(
          $collections,
          $newCollections
        );
        $count += count($newCollections);
        Yii::debug("Retrieved $count of $nodeCount collections...");
      } while (count($collections) < $nodeCount);
      Yii::$app->cache->set("zotero-$datasourceName-collections", $collections);
    } else {
      Yii::debug("Using cached collection data...");
      $collections = $cachedCollections;
    }

    // order by parent
    $parents= [];
    foreach ($collections as $collection) {
      $parentKey = $collection['data']['parentCollection'];
      $parentNodeId = $this->getNodeIdFromKey($parentKey);
      $parents[$parentNodeId][] = $collection;
    }
    // transform into simple tree model, starting with the root node
    $cachedNodeDataList = [];
    $createChildNodes = function($parentKey) use (&$parents, $datasourceName, &$createChildNodes, &$cachedNodeDataList) {
      $parentNodeId = $this->getNodeIdFromKey($parentKey);
      $children = $parents[$parentNodeId];
      // sort by name
      usort($children, function ($a, $b) {
        $an = $a['data']['name'];
        $bn = $b['data']['name'];
        if ($an[0] === "_" and $bn[0] !== "_") return -1;
        if ($bn[0] === "_" and $an[0] !== "_") return 1;
        return strcmp($an, $bn);
      });
      foreach ($children as $child) {
        $cachedNodeDataList[] = $this->nodeData($child, $datasourceName);
      }
      foreach ($children as $child) {
        $childCount = $child['meta']['numCollections'];
        if ( $childCount > 0) {
          Yii::debug("Adding $childCount children for folder " . $child['data']['name']);
          $createChildNodes($child['data']['key']);
        }
      }
    };
    $createChildNodes(0);
    Yii::$app->cache->set("zotero-$datasourceName-versions", $versions);
    Yii::$app->cache->set("zotero-$datasourceName-nodes", $cachedNodeDataList);
    return $cachedNodeDataList;
  }

  /**
   * Assigns a numeric id to the zotero key. Works only for the current request
   * since only in-memory assignment.
   * @param $key
   * @return int
   */
  protected function getNodeIdFromKey($key) {
    static $map=[];
    static $index=0;
    if (!$key) return 0;
    if (!isset($map[$key])) {
      $map[$key] = ++$index;
    }
    return $map[$key];
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

//  /**
//   * @param $datasourceName
//   * @param $parentKey
//   */
//  protected function requestSubfolders($datasourceName, $parentKey) {
//    Client::execute("zotero.collection.add-subfolders", [$datasourceName, $parentKey]);
//  }

//  /**
//   * @param $datasourceName
//   * @param $parentKey
//   * @return string
//   * @throws \lib\exceptions\UserErrorException
//   */
//  public function actionAddSubfolders($datasourceName) {
//    $nodeDataList = $this->nodeDataList($datasourceName);
//    $this->dispatchClientMessage(Folder::MESSAGE_CLIENT_ADD,[
//        'datasource'  => $datasourceName,
//        'modelType'   => "folder",
//        'nodeData'    => $nodeDataList,
//        'transactionId' => 0
//      ]);
//    return "Returned data for " . count($nodeDataList) . " subfolder(s)";
//  }
}
