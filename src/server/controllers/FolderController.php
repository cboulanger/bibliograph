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

use app\controllers\TrashController;
use app\models\Reference;
use lib\dialog\Confirm;
use lib\dialog\Form;
use lib\exceptions\UserErrorException;
use Yii;

use lib\controllers\ITreeController;
use app\controllers\AppController;
use app\models\Datasource;
use app\models\Folder;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\StaleObjectException;

class FolderController extends AppController //implements ITreeController
{
  use traits\FormTrait;
  use traits\FolderDataTrait;

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
      throw new \lib\exceptions\UserErrorException(Yii::t('app', "Database {datasource} does not exist.", [
        'datasource' => $datasource
      ]));
    }
    /** @var ActiveQuery $query */
    $query = $modelClass::find()->select("id");
    if ($this->getActiveUser()->isAnonymous()) {
      $query = $query->where(['public' => true]);
    }
    $nodeIds = $query->column();
    $nodeData = array_map(function ($id) use ($datasource) {
      return $this->getNodeData($datasource, $id);
    }, $nodeIds);
    return [
      'nodeData' => $nodeData,
      'statusText' => count($nodeData) . " Folders loaded."
    ];
  }

  /**
   * Edit folder data
   * @param $datasource
   * @param $folderId
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   * @throws \Exception
   */
  public function actionEdit($datasource, $folderId)
  {
    $this->requirePermission("folder.edit");
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
   * @param $datasource
   * @param $folderId
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionSave($data=null, $datasource=null, $folderId=null)
  {
    if ($data === null) return "ABORTED";
    $this->requirePermission("folder.edit");
    /** @var Folder $folder */
    $folder = static::getRecordById($datasource, $folderId);
    try {
      $data = Form::parseResultData($folder, $data);
      Yii::debug($data);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage(),null, $e);
    }
    try {
      $folder->setAttributes($data);
      Yii::debug($folder->getAttributes());
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
   * @throws \JsonRpc2\Exception
   */
  public function actionVisibilityDialog($datasource, $folderId)
  {
    $this->requirePermission("folder.edit");
    $folder = $this->getRecordById($datasource, $folderId);
    Form::create(
      Yii::t('app', "Change the visibility of the folder"),
      [
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
      ], true,
      Yii::$app->controller->id, "visibility-change", [$datasource, $folderId]
    );
  }

  /**
   * Change the public state
   *
   * @param $data
   * @param string $datasource
   * @param int $folderId
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionVisibilityChange($data=null, string $datasource=null, int $folderId=null)
  {
    if ($data === null) return "ABORTED";
    $this->requirePermission("folder.edit");
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
   * @throws \JsonRpc2\Exception
   */
  public function actionAddDialog($datasource, $folderId)
  {
    $this->requirePermission("folder.add");
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
   * @throws \JsonRpc2\Exception
   */
  public function actionCreate($data, $datasource, $parentFolderId)
  {
    $this->requirePermission("folder.add");
    if ($data === null or $data->label == "") return "ABORTED";
    $folderClass = $this->getControlledModel($datasource);
    /** @var Folder $parentFolder */
    $parentFolder = $folderClass::findOne($parentFolderId);
    if( ! $parentFolder and $parentFolderId ){
      throw new UserErrorException("Parent folder #$parentFolderId does not exist.");
    }
    // child folder
    /** @var Folder $folder */
    $folder = new $folderClass([
      'parentId' => $parentFolderId,
      'label' => $data->label,
      'searchfolder' => $data->searchfolder,
      'childCount' => 0,
      'position' => 0
    ]);
    try {
      $folder->save();
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    if( ! $parentFolderId ){
      // root node
      return "Created new top folder";
    }
    return "Created new folder";
  }

  /**
   * Creates a confimation dialog to remove a folder
   * @param $datasource
   * @param $folderId
   * @return string Diagnostic message
   * @throws UserErrorException
   * @throws \JsonRpc2\Exception
   */
  public function actionRemoveDialog($datasource, $folderId)
  {
    $this->requirePermission("folder.remove");
    /** @var Folder $folder */
    $folder = $this->getRecordById($datasource, $folderId);
    // root folder?
    if ($folder->parentId == 0) {
      throw new UserErrorException(Yii::t('app', "Top folders cannot be deleted."));
    }
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
   * @throws \JsonRpc2\Exception
   */
  public function actionRemove($data, $datasource, $folderId)
  {
    if (!$data) return "ABORTED";
    $this->requirePermission("folder.remove");
    /** @var Folder $folder */
    $folder = $this->getRecordById($datasource, $folderId);
    if ($folder->parentId == 0) {
      throw new UserErrorException(Yii::t('app', "Top folders cannot be deleted."));
    }

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
    $this->setFolderMarkedDeleted($folder, true);
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
   */
  public function setFolderMarkedDeleted(\app\models\Folder $folder, $value)
  {
    // mark folder (un)deleted
    $folder->markedDeleted = $value;
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
          $folder->unlink("references", $reference);
          $folder->getReferenceCount(true);
        } else {
          // if it is contained in this folder only,  mark deleted 
          $folder->markedDeleted = true;
          try {
            $folder->save();
          } catch (Exception $e) {
            Yii::error($e);
          }
        }
      } else {
        $folder->markedDeleted = false;
        try {
          $folder->save();
        } catch (Exception $e) {
          Yii::error($e);
        }
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
   * @param $datasource
   * @param $folderId
   * @param $parentId
   * @throws UserErrorException
   * @return string "OK"
   * @throws \JsonRpc2\Exception
   */
  public function actionMove($datasource, $folderId, $parentId)
  {
    $this->requirePermission("folder.move");
    if ($folderId == $parentId) {
      throw new UserErrorException(Yii::t('app', "Folder cannot be moved on itself."));
    }
    $id = $parentId;
    do {
      $folder = $this->getRecordById($datasource, $id);
      if ($folder->id == $folderId) {
        throw new UserErrorException(Yii::t('app', "Parent node cannot be moved on a child node"));
      }
      $id = $folder->parentId;
    } while ($id !== 0);

    // change folder parent
    $folder = $this->getRecordById($datasource, $folderId);

    // root folder?
    if ($folder->parentId == 0) {
      throw new UserErrorException(Yii::t('app', "Top folders cannot be moved."));
    }

    $folder->parentId = $parentId;
    $folder->save();

    // mark deleted if moved into trash folder
    $trashFolder = TrashController::getTrashfolder($datasource);
    if ($trashFolder) {
      $this->setFolderMarkedDeleted($folder, $parentId === $trashFolder->id);
    }
    return "OK";
  }

  /**
   * Changes the position of a folder within its siblings
   * @param $datasource
   * @param $folderId
   * @param $position
   * @return string "OK"
   * @throws \JsonRpc2\Exception
   */
  public function actionPositionChange($datasource, $folderId, $position)
  {
    $this->requirePermission("folder.move");
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
