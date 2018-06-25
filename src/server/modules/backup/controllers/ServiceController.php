<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 12.06.18
 * Time: 10:46
 */

namespace app\modules\backup\controllers;

use app\controllers\AppController;
use app\controllers\traits\AuthTrait;
use app\controllers\traits\DatasourceTrait;

class ServiceController extends AppController
{
  use AuthTrait;
  use DatasourceTrait;
  use ServicesTrait;

  /**
   * @param $datasource
   * @throws \JsonRpc2\Exception
   */
  public function actionList($datasource)
  {
    $this->requirePermission("backup.restore", $datasource);
    $files = $this->listBackupFiles($this->datasource($datasource));
    rsort($files);
    return $this->createFormOptions($files);
  }
}