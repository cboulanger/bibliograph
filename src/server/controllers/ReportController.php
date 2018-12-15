<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 2018-12-14
 * Time: 09:31
 */

namespace app\controllers;

use app\controllers\traits\AuthTrait;
use app\controllers\traits\TableTrait;
use app\models\Datasource;
use app\models\Folder;
use app\models\Reference;
use lib\exceptions\UserErrorException;
use yii\helpers\Html;
use Yii;

class ReportController extends \yii\web\Controller
{

  use AuthTrait;
  use TableTrait;

  public function actionCreate($datasource, $nodeId)
  {
    Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
    $level = 1;
    $folders = [
      [$level, (int) $nodeId]
    ];
    $traverseChildren = function($folderId, $level) use(&$folders, &$traverseChildren, $datasource){
      foreach ($this->getChildFolderIds($datasource, $folderId) as $id){
        $folders[]=[$level, (int) $id];
        $traverseChildren( $id,$level+1);
      }
    };
    $traverseChildren((integer)$nodeId,$level+1);
    $params = [
      'datasource' => $datasource,
      'folders'    => $folders,
      'level'      => 1,
      'controller' => $this
    ];
    return $this->renderPartial('subtree', $params);
  }

  /**
   * @param string $datasourceName
   * @param int $folderId
   * @return Folder
   */
  public function getFolder($datasourceName, $folderId) {
    $folder =  (Datasource::in($datasourceName, "folder"))::findOne($folderId);
    if (!$folder){
      throw new UserErrorException("Invalid folder id $folderId");
    }
    return $folder;
  }

  /**
   * @param string $datasource
   * @param int $folderId
   * @return array
   */
  public function getReferences($datasourceName, $folderId) {
    $folder = $this->getFolder($datasourceName, $folderId);
    $datasource = Datasource::getInstanceFor($datasourceName);
    if ($folder->searchfolder && $folder->query){
      $query = $this->createActiveQueryFromNaturalLanguageQuery(
        $datasource->getClassFor("reference"),
        $datasourceName,
        $folder->query
      );
    } else {
      $query = (Datasource::in($datasourceName, "reference"))::find()
        ->select('references.*')
        ->alias('references')
        ->joinWith('folders',false)
        ->onCondition(['folderId' => (int) $folderId]);
    }
    //echo $query->createCommand()->rawSql;
    return $query->asArray()->all();
  }

  /**
   * @param string $datasourceName
   * @param int $folderId
   * @return array
   */
  public function getChildFolderIds($datasourceName, $folderId){
    return (Datasource::in($datasourceName, "folder"))::find()
      ->select('id')
      ->where(['parentId'=>$folderId])
      ->column();
  }
}