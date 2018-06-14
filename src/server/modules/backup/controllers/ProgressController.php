<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\modules\backup\controllers;

use app\controllers\traits\AuthTrait;
use app\controllers\traits\DatasourceTrait;
use app\controllers\traits\MessageTrait;
use app\modules\backup\Module;
use lib\dialog\ServerProgress;
use Yii;

/**
 * Class HtmlController
 * Call backup actions using REST request and a HTML response
 * Backup actions emit chunked response for the ProgressController Widget
 * @package app\modules\backup
 */
class ProgressController extends \yii\web\Controller
{
  use ServicesTrait;
  use DatasourceTrait;
  use AuthTrait;

  /**
   * Service to backup a datasource's model data
   * @param string $datasource Name of the datasource
   * @param string $id The widgetId of the widget displaying
   *    the progress of the backup
   * @param string|null $comment Optional comment
   * @throws \JsonRpc2\Exception
   */
  public function actionCreate(string $datasource, string $id, string $comment = null)
  {
    $this->requirePermission("backup.create");
    $progressBar = new ServerProgress($id);
    try {
      $this->createBackup($this->datasource($datasource, true), $progressBar, $comment);
      $progressBar->complete(Yii::t(Module::CATEGORY, "Backup has been created."));
    } catch (\RuntimeException $e) {
      $progressBar->error($e->getMessage());
    }
  }

  /**
   * @param string $datasource
   * @param string $file
   * @param string $id
   * @throws \JsonRpc2\Exception
   */
  public function actionRestore(string $datasource, string $file, string $id)
  {
    $this->requirePermission("backup.restore");
    $progressBar = new ServerProgress($id);
    try {
      $result = $this->restoreBackup($this->datasource($datasource, true), $file, $progressBar);
      Yii::debug($result,Module::CATEGORY);
      if( $result['errors'] > 0 ){
        throw new \RuntimeException("Restore unsuccessful. Please check log files.");
      }
      $progressBar->dispatchClientMessage("backup.restored", ["datasource" => $datasource]);
      Yii::$app->message->broadcast("backup.restored", ["datasource" => $datasource]);
      $progressBar->complete();
    } catch (\RuntimeException $e) {
      $progressBar->error($e->getMessage());
    }
  }
}