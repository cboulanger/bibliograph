<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 17.03.18
 * Time: 22:30
 */

namespace app\controllers\traits;

use app\controllers\events\BibliographEvents;
use app\models\Datasource;
use app\models\User;
use lib\exceptions\UserErrorException;
use RuntimeException;
use yii\db\ActiveQuery;
use Yii;

trait DatasourceTrait
{

  /**
   * @return User
   */
  abstract function getActiveUser();

  /**
   * Returns the datasource instance which has the given named id.
   * By default, checks the current user's access to the datasource.
   * @param string $datasourceName
   *    The named id of the datasource
   * @param bool $checkAccess
   *    Optional. Whether to check the current user's access to the datasource
   *    Defaults to true
   * @return \app\models\Datasource
   * @throws UserErrorException
   */
  public function datasource($datasourceName, $checkAccess = true)
  {
    try {
      $instance = Datasource:: getInstanceFor($datasourceName);
    } catch (\InvalidArgumentException $e) {
      throw new UserErrorException(
        Yii::t('app', "Datasource '{datasource}' does not exist", [
          'datasource' => $datasourceName
        ])
      );
    }
    if ($checkAccess) {
      /** @var User $user */
      $user = Yii::$app->user->identity;
      $myDatasources = $user->getAccessibleDatasourceNames();
      if (!in_array($datasourceName, $myDatasources) and !$this->getActiveUser()->isAdmin()) {
        throw new UserErrorException(
          Yii::t('app', "You do not have access to datasource '{datasource}'", [
            'datasource' => $datasourceName
          ])
        );
      }
    }
    return $instance;
  }

  /**
   * Returns the query that belongs the model of the given type in the given
   * datasource
   * @param string $datasource
   * @param string $type
   * @return ActiveQuery
   */
  protected function findIn($datasource, $type)
  {
    return Datasource::getInstanceFor($datasource)->getClassFor($type)::find();
  }

  /**
   * Returns the class name of the given model type of the controller as
   * determined by the datasource
   * @param string $datasourceName
   * @param string $modelType
   * @param bool $checkAccess
   *    Optional. Whether to check the current user's access to the datasource
   *    Defaults to true
   * @return string
   * @throws RuntimeException
   */
  public function getModelClass($datasourceName, $modelType, $checkAccess = true)
  {
    return $this->datasource($datasourceName, $checkAccess)->getClassFor($modelType);
  }

  /**
   * Returns the class name of the main model type of the controller as
   * determined by the datasource
   * @param string $datasourceName
   * @param bool $checkAccess
   *    Optional. Whether to check the current user's access to the datasource
   *    Defaults to true
   * @return string
   * @throws RuntimeException
   * @todo rename to getControlledModelClass
   */
  public function getControlledModel($datasourceName, $checkAccess = true)
  {
    return $this->getModelClass($datasourceName, static::$modelType, $checkAccess);
  }

  /**
   * Returns a query for the record with the given id
   *
   * @param string $datasourceName
   * @param int $id
   * @param bool $checkAccess
   *    Optional. Whether to check the current user's access to the datasource
   *    Defaults to true
   * @return \yii\db\ActiveRecord
   * @throws RuntimeException
   */
  public function getRecordById($datasourceName, $id, $checkAccess = true)
  {
    $model = $this->getControlledModel($datasourceName, $checkAccess):: findOne($id);
    if (is_null($model)) {
      throw new \InvalidArgumentException("Model of type " . static::$modelType . " and id #$id does not exist in datasource '$datasourceName'.");
    }
    return $model;
  }
}
