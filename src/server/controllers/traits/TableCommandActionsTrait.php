<?php

namespace app\controllers\traits;

use app\controllers\TrashController;
use app\models\Folder;
use app\models\Reference;
use lib\dialog\Confirm;
use lib\exceptions\RecordExistsException;
use lib\exceptions\UserErrorException;
use lib\Validate;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Exception;

trait TableCommandActionsTrait {
  /**
   * Used by confirmation actions
   * @var bool
   */
  protected $confirmed = false;

  //abstract function
  /**
   * Confirm that a reference should be moved to the trash folder
   * @param string $datasource
   * @param int $folderId
   * @param string $ids
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionConfirmMoveToTrash($confirmed, string $datasource, $ids )
  {
    if( ! $confirmed ) return "Remove action was cancelled.";
    $this->confirmed = "all";
    return $this->actionRemove($datasource, 0, $ids );
  }

  /**
   * Move references from one folder to another folder
   *
   * @param string $datasource If true, it is the result of the confirmation
   * @param int $folderId The folder to move from
   * @param int $targetFolderId The folder to move to
   * @param string $ids The ids of the references to move, joined by  a comma
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionMove(string $datasource, int $folderId, int $targetFolderId, string $ids)
  {
    $this->requirePermission("reference.move", $datasource);

    $referenceClass = $this->getControlledModel($datasource);
    $folderClass = static:: getFolderModel($datasource);
    /** @var Folder $sourceFolder */
    $sourceFolder = $folderClass::findOne($folderId);
    /** @var Folder $targetFolder */
    $targetFolder = $folderClass::findOne($targetFolderId);

    $trashFolder = TrashController::getTrashFolder($datasource);
    if( $trashFolder and $targetFolder->id === $trashFolder->id){
      return $this->actionRemove( $datasource, 0, $ids );
    }

    try {
      Validate::isNotNull($targetFolder, "Folder #$targetFolderId does not exist");
      Validate::isNotNull($sourceFolder, "Folder #$folderId does not exist");
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    /** @var ActiveQuery $query */
    $query = $referenceClass::find()->where(['in', 'id', explode(",",$ids)]);
    $references = $query->all();

    try {
      return $this->move($references, $datasource, $sourceFolder, $targetFolder);
    } catch (RecordExistsException $e) {
      throw new UserErrorException($e->getMessage());
    }
  }

  /**
   * Move reference from one folder to another folder
   *
   * @param Reference[]|int[] $references either an array of reference
   *    objects or an array of the ids of that object
   * @param string $datasource
   * @param Folder $sourceFolder
   * @param Folder $targetFolder
   * @return string "OK"
   * @throws RecordExistsException
   */
  public function move(
    array $references,
    string $datasource,
    Folder $sourceFolder,
    Folder $targetFolder )
  {
    $ids = [];
    foreach ($references as $reference) {
      if (is_numeric($reference)) {
        $ids[] = $reference;
        $reference = $this->getRecordById($datasource, $reference);
      } elseif($reference instanceof Reference)  {
        $ids[] = $reference->id;
      } else {
        throw new \InvalidArgumentException("Invalid reference '$reference'");
      }

      if( $sourceFolder->id === $targetFolder->id ){
        throw new RecordExistsException(
          Yii::t('app',"At least one record already exists in folder {name}.",[
            'name' => $targetFolder->label
          ])
        );
      }

      // unlink source folder
      try{
        $sourceFolder->unlink("references", $reference, true);
      } catch (Exception $e){
        Yii::error($e);
      }
      // link target folder
      try{
        $targetFolder->link("references", $reference);
      } catch (Exception $e){
        // ignore duplicate links
        if ($e->getCode() !== 1062){
          Yii::error($e);
        }
      }
    }

    // update reference count
    try {
      $sourceFolder->getReferenceCount(true);
      $targetFolder->getReferenceCount(true);
    } catch (Exception $e) {
      Yii::error($e);
    }

    // display change on connected clients

    if (count($ids)) {
      $this->broadcastClientMessage("reference.removeRows", [
        'datasource' => $datasource,
        'folderId' => $sourceFolder->id,
        'query' => null,
        'ids' => $ids
      ]);
    }
    $count = count($ids);
    return "Moved $count references from '{$sourceFolder->label}' to '{$targetFolder->label}'.";
  }

  /**
   * Copies a reference to a folder
   *
   * @param string $datasource
   * @param int $targetFolderId
   * @param string $ids Numeric ids joined by comma
   * @return string "OK"
   * @throws \lib\exceptions\Exception
   */
  public function actionCopy(string $datasource, int $targetFolderId, string $ids)
  {
    $this->requirePermission("reference.move", $datasource);
    $folderModel = static::getFolderModel($datasource);
    $targetFolder = $folderModel::findOne($targetFolderId);

    try {
      Validate:: isNotNull($targetFolder, "Folder #$targetFolderId does not exist");
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    return $this->copy($datasource, $targetFolder, explode(",",$ids));
  }

  /**
   * Copy reference from one folder to another folder
   * @param string $datasource
   * @param Folder $targetFolder
   * @param Reference[]|int[] $references either an array of reference
   *    objects or an array of the ids of that object
   * @return string Diagnostic message
   */
  public function copy( string $datasource, Folder $targetFolder, array $references)
  {
    foreach ($references as $reference) {
      if (is_numeric($reference)) {
        $reference = $this->getControlledModel($datasource)::findOne($reference);
      }
      if (!($reference instanceof Reference)) {
        Yii::warning("Skipping invalid reference '$reference'");
      }
      try{
        $targetFolder->link("references", $reference);
      } catch (yii\db\IntegrityException $e ){
        throw new UserErrorException(
          Yii::t('app', "The reference is already contained in that folder.")
        );
      }

    }

    // update reference count
    try {
      $targetFolder->getReferenceCount(true);
    } catch (Exception $e) {
      Yii::error($e);
    }
    $count = count($references);
    return "Copied $count references to '{$targetFolder->label}'.";
  }

  /**
   * Removes all references from a folder
   *
   * @param string $datasource
   * @param int $folderId
   * @throws \lib\exceptions\Exception
   */
  public function actionEmptyFolder($datasource, $folderId)
  {
    $this->requirePermission("reference.batchedit", $datasource);

    $folderModel = static::getFolderModel($datasource);
    /** @var Folder $folder */
    $folder = $folderModel::findOne($folderId);
    /** @var Reference[] $references */
    $references = $folder->getReferences()->all();

    $foldersToUpdate = [$folderId];
    $referencesToTrash = [];

    foreach ($references as $reference) {
      $folderCount = $reference->getFolders()->count();
      $reference->unlink("folders", $folder, true);
      if ($folderCount == 1) {
        $referencesToTrash[] = $reference;
      }
    }
    if (count($referencesToTrash)) {
      $trashFolder = TrashController::getTrashFolder($datasource);
      if ($trashFolder) {
        foreach ($referencesToTrash as $reference) {
          $trashFolder->link("references", $reference);
        }
      }
    }
    foreach ($foldersToUpdate as $fid) {
      /** @var Folder $folder */
      $folder = $folderModel::findOne($fid);
      if (!$folder) {
        Yii:: warning("Folder #$fid does not exist");
      }
      try {
        $folder->getReferenceCount(true);
      } catch (Exception $e) {
        Yii::error($e);
      }
      $this->broadcastClientMessage("folder.reload", array(
        'datasource' => $datasource,
        'folderId' => $fid
      ));
    }
    return "Removed all references from folder #$folderId in $datasource";
  }

  /**
   * Removes references from a folder. If the reference is not contained in any other folder,
   * move it to the trash
   * @param string $datasource The name of the datasource
   * @param int $folderId The numeric id of the folder. If zero, remove from all folders
   * @param string $ids A string of the numeric ids of the references, joined by a comma
   * @return string Diagnostic message
   * @throws \lib\exceptions\Exception
   */
  public function actionRemove(string $datasource, int $folderId, string $ids )
  {
    $this->requirePermission("reference.remove", $datasource);

    if( $folderId === 0 and $this->confirmed !== "all" ){
      Confirm::create(
        Yii::t(self::CATEGORY, "Do you really want to move all copies of the reference(s) to the trash?"),
        null,
        "reference", "confirm-move-to-trash",
        [$datasource, $ids]
      );
      return "Created confirmation dialog.";
    }

    /** @var Reference $referenceClass */
    $referenceClass = $this->getControlledModel($datasource);
    $folderClass = static::getFolderModel($datasource);
    $trashFolder = TrashController::getTrashFolder($datasource);

    // use the first id
    $ids = explode(",",$ids);
    $id = intval($ids[0]);

    // load record and count the number of links to folders
    $reference = $referenceClass::findOne($id);
    if( !$reference ){
      throw new UserErrorException("Reference #$id does not exist.");
    }
    $containedFolderIds = $reference->getReferenceFolders()->select("FolderId")->column();
    $foldersToUpdate = $containedFolderIds;
    $folderCount = count($containedFolderIds);

    /** @var Folder $folder */
    if( $folderId === 0 and $this->confirmed==="all" ){
      // unlink all folders
      foreach ($containedFolderIds as $fid) {
        $folder = $folderClass::findOne(intval($fid));
        $reference->unlink("folders", $folder, true);
        $folderCount--;
      }
    } else {
      // unlink only the current one
      $folder = $folderClass::findOne(intval($folderId));
      $reference->unlink("folders", $folder, true);
    }

    // move to trash if it was contained in one or less folders
    if ( $trashFolder and $folderCount < 2) {
      if( $folder->id === $trashFolder->id ){
        // reference is already in the trash, delete
        try {
          $reference->delete();
        } catch (\Throwable $e) {
          Yii::error($e);
        }
      } else {
        // link with trash folder
        try{
          $trashFolder->link("references", $reference);
        } catch (Exception $e) {
          Yii::error($e);
        }
        // mark as deleted
        $reference->markedDeleted = 1;
        try {
          $reference->save();
        } catch (Exception $e) {
          Yii::error($e->getMessage());
        }
        $foldersToUpdate[] = $trashFolder->id;
      }
    }

    // update reference count in source and target folders
    $foldersToUpdate = array_unique($foldersToUpdate);
    foreach ($foldersToUpdate as $fid) {
      /** @var Folder $folder */
      $folder = $folderClass::findOne($fid);
      if ($folder) {
        try {
          $folder->getReferenceCount(true);
        } catch (Exception $e) {
          Yii::error($e);
        }
      } else {
        Yii::warning("Folder #$fid does not exist.");
      }
    }

    // display change on connected clients
    foreach ($containedFolderIds as $fid) {
      $this->broadcastClientMessage("reference.removeRows", array(
        'datasource' => $datasource,
        'folderId' => intval($fid),
        'query' => null,
        'ids' => [intval($id)]
      ));
    }
    // if there are references left, repeat
    if (count($ids) > 1) {
      array_shift($ids);
      return $this->actionRemove($datasource, $folderId, implode(",",$ids) );
    }
    return "Removed references.";
  }
}
