<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 09.03.18
 * Time: 08:26
 */

namespace modules\z3950\controllers;

use lib\dialog\ServerProgress;
use yii\web\Controller;

class ProgressController extends Controller
{
  /**
   * Executes a Z39.50 request on the remote server. Called
   * by the ServerProgress widget on the client
   *
   * @param string $datasourceName
   * @param $query
   * @param string $progressWidgetId
   * @return string Chunked HTTP response
   * @todo use DTO
   */
  public function actionRequestProgress($datasourceName, $query, $progressWidgetId)
  {
    $progressBar = new ServerProgress($progressWidgetId);
    try {
      $this->module->sendRequest($datasourceName, $query, $progressBar);
      $progressBar->dispatchClientMessage("z3950.dataReady", $query);
      return $progressBar->complete();
    } catch (qcl_server_ServiceException $e) {
      $this->log($e->getMessage(), BIBLIOGRAPH_LOG_Z3950);
      return $progressBar->error($e->getMessage());
    } catch (Exception $e) {
      $this->warn($e->getFile() . ", line " . $e->getLine() . ": " . $e->getMessage());
      return $progressBar->error($e->getMessage());
    }
  }

}