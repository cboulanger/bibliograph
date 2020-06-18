<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2018 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\models\Reference;
use app\models\Datasource;
use app\models\Folder;
use lib\channel\MessageEvent;
use lib\dialog\Confirm;
use lib\dialog\Form;
use lib\exceptions\UserErrorException;
use RuntimeException;
use yii\db\ActiveQuery;
use yii\db\Exception;
use Yii;


class FolderController extends AppController //implements ITreeController
{
  use traits\FormTrait;
  use traits\FolderDataTrait;
  use traits\TableTrait;

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
  static $modelClass = Folder::class;

  /**
   * Virtual folders have an id that counts down from this
   * value, which is the highest value a javascript integer can have
   */
  const VIRTUAL_FOLDER_ID = 9007199254740991;

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
    $query = Folder::find();
    if ($this->getActiveUser()->isAnonymous()) {
      $query = $query->where(['public' => true]);
    }
    $nodeCount = $query->count();
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
    try {
      $modelClass = $this->getControlledModel($datasource);
    } catch (\InvalidArgumentException $e) {
      throw new UserErrorException(Yii::t('app', "Database {datasource} does not exist.", [
        'datasource' => $datasource
      ]));
    }
    /** @var ActiveQuery $query */
    $query = $modelClass::find();
    $query->select("id")->orderBy("parentId,position");
    if ($this->getActiveUser()->isAnonymous()) {
      $query = $query->where(['public' => true]);
    }
    $nodeIds = $query->column();
    $nodeData = array_map(function ($id) use ($datasource) {
      return $this->getNodeData($datasource, $id);
    }, $nodeIds);

    // make sure that parents are placed before their children
    // this is a limitation of the SimpleDataModel
    // @todo rewrite to build the whole tree in memory first and then send the SimpleDataModel view of it
    $orderedNodeData = [];
    $loaded=[];
    $failed=[];
    $isGuestUser = Yii::$app->user->identity->isAnonymous();
    $orphanedFolder = null;
    $orphanedFolderId = null;
    while( count($nodeData) ){
      $node = array_shift($nodeData);
      $id= $node['data']['id'];
      $parentId = $node['data']['parentId'];

      // if the parent hasn't been processed yet, push to the tail of the queue
      if ( ! isset($loaded[$parentId]) and $parentId !== 0) {

        // but only three times to prevent infinite loop if tree is corrupted
        if( !isset($failed[$id]) ){
          $failed[$id] = 0;
        }
        if ($failed[$id]++ <= 3) {
          $nodeData[] = $node;
          continue;
        }

        // otherwise, put into virtual top folder "orphaned"
        // unless user is unauthenticated
        if ($isGuestUser) continue;
        if (!$orphanedFolder) {
          // create this folder first
          $orphanedFolderId = $this->getVirtualFolderId();
          $orphanedFolder = $this->createOrphanedFolder($orphanedFolderId);
          $orderedNodeData[] = &$orphanedFolder;
          $loaded[$orphanedFolderId] = &$orphanedFolder;
        }
        $orphanedFolder['data']['childCount']++;
        $node['data']['parentId'] = $orphanedFolderId;
      }
      // add node to output
      $orderedNodeData[] = $node;
      $loaded[$id] = $node;

      // virtual subfolders, too costly in case of large data, query on demand
      if ($node['data']['query'] && str_contains( $node['data']['query'], "virtsub:" )){
        Yii::$app->eventQueue->add(new MessageEvent([
          'name' => static::MESSAGE_EXECUTE_JSONRPC,
          'data' => ["folder", "create-virtual-folders", [$datasource, $id]]
        ]));
      }
    }
    //$this->addLostAndFound($orderedNodeData);
    return [
      'nodeData' => $orderedNodeData,
      'statusText' => count($orderedNodeData) . " Folders loaded."
    ];
  }

  /**
   * Given a datasource and a folder id, return an event that will retrieve the virtual subfolders
   * of that folder
   * @param string $datasource
   * @param int $parentId
   * @return string
   */
  public function actionCreateVirtualFolders(
    string $datasource,
    int $parentId)
  {
    $folder = Datasource::findOneIn($datasource, "folder", $parentId);
    if (!$folder) throw new UserErrorException("Invalid folder Id $parentId");
    Yii::$app->eventQueue->add(new MessageEvent([
      'name' => Folder::MESSAGE_CLIENT_ADD,
      'data' => [
        'datasource'  => $datasource,
        'modelType'   => "folder",
        'nodeData'    => $this->createVirtualSubfolders($datasource, $parentId, $folder->query),
        'transactionId' => 0
      ]]));
    return "Returned node data of virtual folders";
  }

  /**
   * Return an integer that decrements from the highest javascript integer value
   * @return int
   */
  protected function getVirtualFolderId(){
    $n = 'VIRTUAL_FOLDER_ID';
    $_SESSION[$n] =
      ($_SESSION[$n] ?? null )
        ? $_SESSION[$n]-1
        : self::VIRTUAL_FOLDER_ID;
    return $_SESSION[$n];
  }

  /**
   * Creates "virtual" subfolders, i.e. subfolders that are generated on-the-fly based and contain
   * queries.
   * @param string $datasource
   * @param int $parentId
   * @param string $query
   * @return array  An array of node data
   */
  protected function createVirtualSubfolders(
    string $datasource,
    int $parentId,
    string $query)
  {
    //return [];

    if(!starts_with($query,"virtsub:")){
      throw new UserErrorException("Invalid folder query for virtual folders: $query");
    }
    $field = trim(substr($query,strpos($query,":")+1));
    try{
      $query = Datasource::findIn($datasource,"reference")
        ->select($field)
        ->distinct()
        ->orderBy($field);
      $values = $query->column();
    } catch (\Exception $e){
      Yii::warning($e->getMessage());
      return [];
    }
    // TODO: only for fields that have a separator!! See ReferenceController line 239!
    $separatedValues = [];
    foreach ($values as $value) {
      if( ! $value ) continue;
      if( str_contains($value, ";") ){
        $separatedValues = array_merge($separatedValues, explode(";",$value) );
      } else {
        $separatedValues[]=$value;
      }
    }
    $separatedValues = array_values(array_unique(array_map(function($v){ return trim($v);}, $separatedValues)));
    $collator = new \Collator("de_DE");
    $collator->sortWithSortKeys($separatedValues);
    $limit = 100;
    $count = count($separatedValues);
    $sections = ceil($count/$limit);
    $index = 0;
    $nodeData = [];

    for ($outer=0; $outer <= $sections and $index < $count; $outer++){
      $sectionId = null;
      if ($sections > 1) {
        // compute label
        $sectionId = $this->getVirtualFolderId();
        $first = trim($separatedValues[$index]);
        $last  = trim($separatedValues[min($index + $limit, $count)-1]);
        // this shows that we need a "creator having a role" approach (like in mods/zotero)
        if (in_array($field, ["author","editor","creator","translator"])){
          list($first,) = explode(",", $first);
          list($last,) = explode(",",$last);
        }
        $label =
          mb_strimwidth(trim($first),0,15, "...") .
          " - " .
          mb_strimwidth(trim($last),0,15, "...");
        // create section node
        $node = $this->createVirtualFolder([
          'type'      => 'virtual',
          'id'        => $sectionId,
          'parentId'  => $parentId,
          'icon'      => "icon/16/apps/utilities-graphics-viewer.png",
          'label'     => $label,
          'childCount'  => $limit
        ]);
        $nodeData[] = $node;
      }
      for ($inner=0; $inner < $limit and $index < $count; $inner++){
        $value = trim($separatedValues[$index++]);
        if (!$value) continue;
        // computation of reference count is too expensive for large datasets
        // needs to be configurable since it is machine-dependent
        $referenceCount = $count < $limit*3
          ? (int) Datasource::findIn($datasource,"reference")->where(['like', $field, $value])->count()
          : null;
        $node = $this->createVirtualFolder([
          'type'      => 'virtual',
          'id'        => $this->getVirtualFolderId(),
          'parentId'  => $sectionId ? $sectionId : $parentId,
          'query'     => $field . ' contains "' . $value . '"',
          'icon'      => "icon/16/apps/utilities-graphics-viewer.png",
          'label'     => $value,
          'referenceCount'  => $referenceCount,
          'columnData'      => $referenceCount,
        ]);
        $nodeData[] = $node;
      }
    }
    return $nodeData;
  }

  /**
   * Create a virtual folder (one that does not have a corresponding entry in the folder table)
   * @param array $data A flat map with preset folder properties
   * @return array The node data
   */
  protected function createVirtualFolder(array $data)
  {
    return [
      'isBranch'        => true, //isset($data['isBranch']) ? $data['isBranch']:false,
      'label'           => $data['label'],
      'bOpened'         => false,
      'icon'            => $data['icon'],
      'iconSelected'    => $data['icon'],
      'bHideOpenClose'  => isset($data['childCount']) ? $data['childCount'] === 0 : true,
      // todo: this should take its value from 'columnContent' or similar
      'columnData'      => [ null, isset($data['columnData']) ? $data['columnData']:"" ],
      'data'            => [
        'type'            => isset($data['type']) ? $data['type']:"virtual",
        'id'              => $data['id'],
        'parentId'        => $data['parentId'],
        'query'           => isset($data['query']) ? $data['query'] : null,
        'public'          => false,
        'owner'           => "admin",
        'description'     => isset($data['description']) ? $data['description']:"",
        'datasource'      => null,
        'childCount'      => isset($data['childCount']) ? $data['childCount']:0,
        'referenceCount'  => isset($data['referenceCount']) ? $data['referenceCount']:0,
        'markedDeleted'   => false
      ]
    ];
  }

  /**
   * Create a virtual folder for orphaned folders and references
   * @param int $id The id of the folder
   * @return array The node data
   */
  protected function createOrphanedFolder(int $id)
  {
    return $this->createVirtualFolder([
      'type'        => "virtual",
      'isBranch'    => true,
      'id'          => $id,
      'parentId'    => 0,
      'query'       => null,
      'icon'        => "icon/16/emblems/emblem-important.png",
      'label'       => Yii::t('app', "Orphaned"),
      'childCount'  => 1
    ]);
  }

  /**
   * Edit folder data
   * @param $datasource
   * @param $folderId
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   * @throws \Exception
   */
  public function actionEdit($datasource, $folderId)
  {
    $this->requirePermission("folder.edit", $datasource);
    $model = $this->getRecordById($datasource, $folderId);
    $formData = Form::getDataFromModel($model);
    $label = $model->label;
    $message = "<h3>$label</h3>";
    Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "save",
      array($datasource, $folderId)
    );
    return "Created form to edit folder data.";
  }

  /**
   * Saves the result of the edit() method
   * @param $data
   * @param string $datasource
   * @param int $folderId
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionSave($data=null, string $datasource=null, int $folderId=null)
  {
    if ($data === null) return "ABORTED";
    $this->requirePermission("folder.edit", $datasource);
    /** @var Folder $folder */
    $folder = static::getRecordById($datasource, $folderId);
    try {
      $data = Form::parseResultData($folder, $data);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage(),null, $e);
    }
    try {
      $folder->setAttributes($data);
      $folder->save();
      return "Folder data saved";
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage(),null, $e);
    }
  }

  /**
   * Change the public state - creates dialog event.
   * @param $datasource
   * @param $folderId
   * @return void
   * @throws \lib\exceptions\Exception
   */
  public function actionVisibilityDialog($datasource, $folderId)
  {
    $this->requirePermission("folder.edit", $datasource);
    $folder = $this->getRecordById($datasource, $folderId);
    (new Form())
      ->setMessage(Yii::t('app', "Change the visibility of the folder"))
      ->setFormData([
        'state' => [
          'label' => Yii::t('app', "State"),
          'type' => "SelectBox",
          'options' => [
            ['label' => Yii::t('app', "Folder is publically visible"), 'value' => 1],
            ['label' => Yii::t('app', "Folder is not publically visible"), 'value' => 0]
          ],
          'value' => $folder->public,
          'width' => 300
        ],
        'recurse' => [
          'label' => Yii::t('app', "Depth"),
          'type' => "SelectBox",
          'options' => [
            ['label' => Yii::t('app', "Apply only to the selected folder"), 'value' => 1],
            ['label' => Yii::t('app', "Apply to the selected folder and its subfolders"), 'value' => 0]
          ],
          'value' => false
        ]
      ])
      ->setAllowCancel(true)
      ->setService(Yii::$app->controller->id)
      ->setMethod("visibility-change")
      ->setParams([$datasource, $folderId])
      ->sendToClient();
  }

  /**
   * Change the public state
   *
   * @param $data
   * @param string $datasource
   * @param int $folderId
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionVisibilityChange($data=null, string $datasource=null, int $folderId=null)
  {
    if ($data === null) return "ABORTED";
    $this->requirePermission("folder.edit", $datasource);
    $data = json_decode(json_encode($data), true); // convert to array

    $folderClass = $this->getControlledModel($datasource);
    $ids = [$folderId];
    do {
      $id = array_shift($ids);
      if (!$id) break;
      //$this->debug("> $id ",__CLASS__,__LINE__);
      /** @var Folder $folder */
      $folder = $folderClass::findOne($id);
      $folder->public = $data['state'];
      try {
        $folder->save();
      } catch (Exception $e) {
        throw new UserErrorException($e->getMessage());
      }
      if ($data['recurse']) {
        $ids = array_merge($ids, $folder->getChildIds());
      }
    } while (count($ids));
    return "Changed visibility.";
  }


  /**
   * Action to add a folder. Creates a dialog event
   *
   * @param string $datasource
   * @param int $folderId
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionAddDialog($datasource, $folderId)
  {
    $this->requirePermission("folder.add", $datasource);
    Form::create(
      Yii::t('app', "Please enter the name and type of the new folder:"),
      [
        'label' => [
          'label' => Yii::t('app', "Name"),
          'type' => "textfield",
          'width' => 200
        ],
        'searchfolder' => [
          'label' => Yii::t('app', "Type"),
          'type' => "SelectBox",
          'options' => [
            ['label' => Yii::t('app', "Normal folder"), 'value' => 0],
            ['label' => Yii::t('app', "Search folder"), 'value' => 1]
          ],
          'value' => false
        ]
      ], true,
      Yii::$app->controller->id, "create", array($datasource, $folderId)
    );
    return "Created dialog to add new folder";
  }

  /**
   * Creates a new folder
   * @param $data
   * @param $datasource
   * @param $parentFolderId
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   * @throws Exception
   */
  public function actionCreate($data, $datasource, $parentFolderId)
  {
    $this->requirePermission("folder.add", $datasource);
    if ($data === null or $data->label == "") return "ABORTED";
    $folderClass = $this->getControlledModel($datasource);
    $position = 0;
    if( $parentFolderId ){
      /** @var Folder $parentFolder */
      $parentFolder = $folderClass::findOne($parentFolderId);
      if( ! $parentFolder ){
        throw new UserErrorException("Parent folder #$parentFolderId does not exist.");
      }
      $position = $parentFolder->childCount;
    } else {
      $position = $folderClass::find()->where(['parentId' => 0])->count()-1;
      $trashFolder = TrashController::getTrashFolder($datasource);
      if( $trashFolder ){
        $trashFolder->position = $position+1;
        $trashFolder->save();
      }
    }

    // child folder
    /** @var Folder $folder */
    $folder = new $folderClass([
      'parentId'      => $parentFolderId,
      'label'         => $data->label,
      'searchfolder'  => $data->searchfolder ?? 0, // todo: needed -> change form to choose general type
      'type'          => $data->searchfolder ? "search" : "folder",
      'query'         => $data->query ?? "",
      'childCount'    => 0,
      'position'      => $position,
      'public'        => 0,
      'opened'        => 0
    ]);

    try {
      $folder->save();
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    // if searchfolder, edit right away
    if( $data->searchfolder ){
      return $this->actionEdit($datasource,$folder->id);
    }
    // otherwise, just return
    if( $parentFolderId === 0){
      // root node
      return "Created new top folder";
    }
    return "Created new folder";
  }

  /**
   * Saves the current search query as a subfolder
   * @param string $datasource
   * @param int $parentFolderId
   * @param string $query
   * @return string
   * @throws Exception
   * @throws \lib\exceptions\Exception
   */
  public function actionSaveSearch(string $datasource, int $parentFolderId, string $query)
  {
    $data = (object)[
      'label' => $query,
      'searchfolder' => true,
      'query' => $query
    ];
    return $this->actionCreate($data,$datasource,$parentFolderId);
  }

  /**
   * Creates a confimation dialog to remove a folder
   * @param $datasource
   * @param $folderId
   * @return string Diagnostic message
   * @throws UserErrorException
   * @throws \lib\exceptions\Exception
   */
  public function actionRemoveDialog($datasource, $folderId)
  {
    $this->requirePermission("folder.remove", $datasource);
    /** @var Folder $folder */
    $folder = $this->getRecordById($datasource, $folderId);
    // create dialog
    Confirm::create(
      Yii::t('app', "Do you really want to move the folder '{name}' into the trash?", [
        'name' => $folder->label
      ]),
      null,
      Yii::$app->controller->id, "remove", array($datasource, $folderId)
    );
    return "Created confirmation dialog";
  }

  /**
   * Removes the given folder
   * @param $data
   * @param $datasource
   * @param $folderId
   * @return string "OK"
   * @throws \lib\exceptions\Exception
   */
  public function actionRemove($data, $datasource, $folderId)
  {
    if (!$data) return "ABORTED";
    $this->requirePermission("folder.remove", $datasource);
    /** @var Folder $folder */
    $folder = $this->getRecordById($datasource, $folderId);

    // move folder into trash
    $trashFolder = TrashController::getTrashFolder($datasource);
    if ($trashFolder) {
      if ($folder->parentId == $trashFolder->id) {
        // it is already in the trash, delete right away
        try {
          $folder->delete();
        } catch (\Throwable $e) {
          Yii::error($e);
        }
      } else {
        $folder->parentId = $trashFolder->id;
      }
    }
    // mark references as deleted
    try {
      $this->setFolderMarkedDeleted($folder, true);
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    return "OK";
  }

  /**
   * Marks a folder and its content as deleted or undeleted, depending on the
   * second (boolean) value. If  this value is true, all references in the folder
   * will be removed, leaving only those which are not linked in any other
   * folders, and marking them as deleted. If the value is false, the folders
   * and contained references will be marked "not deleted"
   *
   * @param \app\models\Folder $folder
   * @param bool $value
   * @return void
   * @throws Exception
   */
  public function setFolderMarkedDeleted(\app\models\Folder $folder, bool $value)
  {
    Yii::debug("Marking folder $folder->id as " . ($value ? "deleted" : "NOT deleted") );
    // mark folder (un)deleted
    $folder->markedDeleted = (integer) $value;
    try {
      $folder->save();
    } catch (Exception $e) {
      Yii::warning($e->getMessage());
    }

    // handle contained references
    /** @var Reference[] $references */
    $references = $folder->getReferences()->all();
    foreach ($references as $reference) {
      if ($value) {
        $folderCount = $reference->getFolders()->count();
        if ($folderCount > 1) {
          // if it is contained in other folders, simply unlink reference and folder
          $folder->unlink("references", $reference, true);
          $folder->getReferenceCount(true);
          continue;
        } else {
          // if it is contained in this folder only,  mark deleted
          $reference->markedDeleted = 1;
        }
      } else {
        $reference->markedDeleted = 0;
      }
      try {
        $reference->save();
      } catch (Exception $e) {
        Yii::error($e);
      }
    }

    // child folders
    $childFolders = $folder->getChildren();
    /** @var Folder $folder */
    foreach ($childFolders as $folder) {
      $this->setFolderMarkedDeleted($folder, $value);
    }
  }

  /**
   * Move a folder to a different parent
   * @param string $datasource
   * @param int $folderId
   * @param int $parentId
   * @throws UserErrorException
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionMove(string $datasource, int $folderId, int $parentId)
  {
    $this->requirePermission("folder.move", $datasource);
    if ($folderId === $parentId) {
      throw new UserErrorException(Yii::t('app', "Folder cannot be moved on itself."));
    }
    $id = $parentId;
    do {
      $folder = $this->getRecordById($datasource, $id);
      if( ! $folder ){
        throw new RuntimeException("Invalid parent folder id.");
      }
      if ($folder->id === $folderId) {
        throw new UserErrorException(Yii::t('app', "Parent node cannot be moved on a child node"));
      }
      $id = $folder->parentId;
    } while ($id !== 0);

    // change folder parent
    /** @var Folder $folder */
    $folder = $this->getRecordById($datasource, $folderId);

    $folder->parentId = $parentId;
    $folder->save();

    // mark (un)deleted if moved into/out of trash folder
    $trashFolder = TrashController::getTrashfolder($datasource);
    if ($trashFolder) {
      try {
        $this->setFolderMarkedDeleted($folder, $parentId === $trashFolder->id);
      } catch (Exception $e) {
        Yii::warning($e);
        throw new UserErrorException($e->getMessage());
      }
    }
    return "OK";
  }

  /**
   * Copies the given folder into the parent folder including all references contained. If copying within the #
   * same datasource, this creates a link, otherwise new references are created.
   * @param string $from_datasource
   * @param int $from_folderId
   * @param string $to_datasource
   * @param int $to_parentId
   * @throws \lib\exceptions\Exception
   */
  public function actionCopy(string $from_datasource, int $from_folderId, string $to_datasource, int $to_parentId ){
    $this->requirePermission("folder.move", $from_datasource); // Todo: needs its own permission
    $this->requirePermission("folder.move", $to_datasource); // Todo: needs its own permission
    // source
    $findInSource = Datasource::findIn($from_datasource, "folder");
    /** @var Folder $from_folder */
    $from_folder = $findInSource->where(['id'=> $from_folderId])->one();
    if( ! $from_folder ){
      throw new \InvalidArgumentException("Source folder #$from_folderId does not exist");
    }
    // target
    $findInTarget = Datasource::findIn($to_datasource, "folder");
    $to_folder = $findInTarget->where(['id'=> $to_parentId])->one();
    if( ! $to_folder and $to_parentId !== 0 ){
      throw new \InvalidArgumentException("Target parent folder #$to_parentId does not exist");
    }
    // create target folder
    $to_folderClass = Datasource::getInstanceFor($to_datasource)->getClassFor("folder");
    /** @var Folder $to_folder */
    $to_folder = new $to_folderClass($from_folder->getAttributes());
    $to_folder->id = null;
    $to_folder->parentId = $to_parentId;
    try {
      $to_folder->save();
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage());
    }

    // copy references
    $isSameDatasource = $from_datasource === $to_datasource;
    $clientQueryData = $this->createClientQueryData($from_folder,"reference");
    $from_refClass = $this->getModelClass($from_datasource, "reference");
    $refQuery = $this->transformClientQuery( $clientQueryData, $from_refClass);
    /** @var Reference[] $references */
    $references = $refQuery->all();
    $to_refClass = $this->getModelClass($to_datasource, "reference");
    $count_updated = 0;
    $count_created = 0;
    if( count($references)) {
      /** @var Reference $copy */
      $copy = new $to_refClass();
      $common_properties = array_diff(
        array_intersect($copy->attributes(), $references[0]->attributes()),
        ['id', 'parentId']
      );
      $findInTarget = Datasource::findIn($to_datasource,"reference");
      foreach ($references as $reference) {
        if ($isSameDatasource) {
          // within the same datasource, link reference to folder
          $to_folder->link("references", $reference);
        } else {
          // try to find existing copy by UUID
          /** @var Reference $copy */
          $copy = $findInTarget->where(['uuid' => $reference->uuid])->one();
          if( $copy ) {
            // TODO skip when copy is newer?
            $count_updated++;
          } else {
            $copy = new $to_refClass();
            $count_created++;
          }
          $copy->setAttributes($reference->getAttributes($common_properties));
          try {
            $copy->save();
            // link to folder
            try{
              $to_folder->link("references", $copy);
            } catch(\Exception $e){
              // ignore if already linked
            }
          } catch (\Throwable $e) {
            throw new UserErrorException($e->getMessage());
          }
        }
      }
    }

    // recompute reference count
    $to_folder::setDatasource($to_datasource);
    $to_folder->referenceCount = 0;
    try {
      $to_folder->save();
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    Yii::debug("$count_created reference created, $count_updated updated.", __METHOD__);
    return sprintf("%s references copied into new folder '%s'",  count($references), $to_folder->label);
  }


  /**
   * Changes the position of a folder within its siblings
   * @param $datasource
   * @param $folderId
   * @param $position
   * @return string "OK"
   * @throws \lib\exceptions\Exception
   */
  public function actionPositionChange($datasource, $folderId, $position)
  {
    $this->requirePermission("folder.move", $datasource);
    /** @var Folder $folder */
    $folder = $this->getRecordById($datasource, $folderId);
    $folder->changePosition($position);
    // notify clients
    $this->broadcastClientMessage(
      "folder.node.reorder", array(
        'datasource' => $datasource,
        'modelType' => "folder",
        'nodeId' => $folderId,
        'parentNodeId' => $folder->parentId,
        'position' => $position,
        'transactionId' => $folder->transactionId
      )
    );
    return "OK";
  }
}
