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

use app\controllers\AppController;
use app\modules\backup\Module;
use lib\dialog\Alert;
use lib\dialog\Confirm;
use lib\dialog\Form;
use lib\dialog\Popup;
use lib\exceptions\UserErrorException;
use Yii;

class UiController extends AppController
{
  use ServicesTrait;

  /**
   * @param $datasource
   * @param $token
   * @throws \JsonRpc2\Exception
   */
  public function actionConfirmRestore($datasource, $token)
  {
    $this->requirePermission("backup.restore", $datasource);
    $msg = Yii::t(Module::CATEGORY, "Do you really want to replace Database '{datasource}' with a backup?", [
      'datasource' => $datasource
    ]);
    (new Confirm())
      ->setMessage($msg)
      ->setRoute("backup/ui/choose-backup")
      ->setParams([$datasource, $token])
      ->sendToClient();
    return "Created confirmation dialog";
  }

  /**
   * Service to present the user with a choice of backups
   * @param $form
   * @param $datasource
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionChooseBackup($form, string $datasource, string $token)
  {
    if ($form === false) {
      return "ABORTED";
    }
    $this->requirePermission("backup.restore", $datasource);

    $files = $this->listBackupFiles($this->datasource($datasource));
    rsort($files);

    $options = $this->createFormOptions($files);
    if (!count($options)) {
      throw new UserErrorException("No backup sets available.");
    }

    $formData = [
      'file' => [
        'label' => Yii::t(Module::CATEGORY, "Backup from "),
        'type' => "selectbox",
        'options' => $options,
        'width' => 200,
      ]
    ];
    $message = Yii::t(Module::CATEGORY,
      "Please select the backup set to restore into database '{datasource}'",
      [ 'datatsource' => $datasource ]
    );

    (new Form())
      ->setFormData($formData)
      ->setMessage($message)
      ->setAllowCancel(true)
      ->setRoute("backup/ui/handle-choose-backup")
      ->setParams([$datasource, $token])
      ->sendToClient();

    return "Choose backup form created";
  }

  /**
   * @param $data
   * @param $datasource
   * @return string
   */
  public function actionHandleChooseBackup($data, $datasource, $token)
  {
    if ($data === null) {
      return "Aborted choose backup.";
    }
    $this->dispatchClientMessage(
      "backup.restore",
      [
        'datasource' =>  $datasource,
        'file'  => $data->file,
        'token' => $token
      ]
    );
    return "OK";
  }

  /**
   * Confirmation dialog for deleting backups
   * @throws \JsonRpc2\Exception
   * @return string diagnostic message
   */
  public function actionChooseDelete($datasource)
  {
    $this->requirePermission("backup.delete", $datasource);
    $days = Yii::$app->config->getPreference("backup.daysToKeepBackupFor");
    $timestamp = time() - ($days * 84400);
    $formData = [
      'date' => [
        'label' => Yii::t(Module::CATEGORY,"Delete backups older than"),
        'type' => "datefield",
        'value' => $timestamp * 1000,
        'width' => 200
      ]
    ];

    (new Form)
      ->setMessage( Yii::t(Module::CATEGORY,
        "Delete backups of datasource '{datasource}':",
        [ 'datasource' => $datasource ] ))
      ->setFormData($formData)
      ->setAllowCancel(true)
      ->setRoute("backup/ui/confirm-delete")
      ->setParams([$datasource])
      ->sendToClient();

    return "Created confirm delete dialog";
  }

  /**
   * Service to delete all backups of this datasource older than one day
   * @throws \JsonRpc2\Exception
   */
  public function actionConfirmDelete($data, $datasource)
  {
    if (!$data) return "CANCELLED";

    $this->requirePermission("backup.delete", $datasource);

    $date = strtotime($data->date);
    $files = $this->listBackupFiles($datasource);
    $filesToDelete = [];

    foreach ($files as $file) {
      list(, $timestamp) = $this->parseBackupFilename($file);
      if ($timestamp < $date) {
        $filesToDelete[] = $file;
      }
    }

    if (count($filesToDelete) == 0) {
      return new UserErrorException(Yii::t(Module::CATEGORY,"No backups found."));
    }

    (new Confirm())
      ->setMessage( Yii::t(
        Module::CATEGORY,
        "Really delete {count} backups?",
        [ 'count' => count($filesToDelete)]
      ))
      ->setRoute("backup/ui/handle-confirm-delete")
      ->setParams([$this->shelve($filesToDelete)])
      ->sendToClient();

    return "Action actionConfirmDelete completed.";
  }

  /**
   * @param $ok
   * @param $shelfId
   * @return string Diagnostic message
   */
  public function actionHandleConfirmDelete($ok, $shelfId)
  {
    if (!$ok) return "CANCELLED";

    list($files) = $this->unshelve($shelfId);

    $problem = false;
    $filesDeleted = 0;
    foreach ($files as $file) {
      if (!@unlink($file)) {
        $problem = true;
        Yii::warning("Cannot delete backup file '$file'", Module::CATEGORY);
      } else {
        $filesDeleted++;
      }
    }
    $msg = Yii::t(Module::CATEGORY,
      "{count} backups were deleted.",
      ['count' => $filesDeleted]
    );
    if ($problem) {
      $msg .=  "\n" .Yii::t(Module::CATEGORY,"There was a problem. Please contact administrator.");
    }
    return "Action actionHandleConfirmDelete completed.";
  }

  /**
   * Service to present the user with a choice of backups
   * @param $form
   * @param $datasource
   * @return string
   * @throws \JsonRpc2\Exception
   */
  public function actionChooseDownload($datasource)
  {
    $this->requirePermission("backup.download", $datasource);

    $files = $this->listBackupFiles($datasource);
    rsort($files);

    $options = $this->createFormOptions($files);
    if (!count($options)) {
      throw new UserErrorException("No backup sets available.");
    }

    $formData = array(
      'file' => array(
        'label' => Yii::t( Module::CATEGORY, "Choose file:"),
        'type' => "selectbox",
        'options' => $options,
        'width' => 200,
      )
    );

    (new Form)
      ->setMessage(Yii::t(
        Module::CATEGORY,
        "Please select the backup set of datasource '{datasource}' to download",
        ['datasource' => $datasource]
      ))
      ->setFormData($formData)
      ->setAllowCancel(true)
      ->setRoute('backup/ui/start-download')
      ->setParams([$datasource])
      ->sendToClient();
  }

  /**
   * Service to trigger download of backup file by the client.
   * @param object $data Form data from the previous dialog
   * @param string $datasource
   * @return string
   */
  public function actionStartDownload($data, $datasource)
  {
    throw new \BadMethodCallException("not implememted");
    $file = basename($data->file);
    $url ="";
    (new Popup(""))->sendToClient(); // hide the popup
    $this->dispatchClientMessage("window.location.replace", array(
      'url' => $url
    ));
    return "OK";
  }
}
