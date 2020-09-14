<?php

namespace app\modules\converters\controllers;
use app\controllers\traits\AuthTrait;
use app\controllers\traits\DatasourceTrait;
use app\controllers\traits\TableControllerTrait;
use app\models\Datasource;
use app\models\ExportFormat;
use app\models\Folder;
use lib\exceptions\UserErrorException;
use Yii;

/**
 * Class DownloadController
 * @package app\modules\converters\controllers
 */
class DownloadController extends \yii\web\Controller
{
  use AuthTrait;
  use DatasourceTrait;
  use TableControllerTrait;

  /**
   * FIXME Fix to suppress Error, probably very bad.
   * @inheritdoc
   */
  public $enableCsrfValidation = false;

  /**
   * Creates a HTTP response that initiates a file download with the
   * exported data.
   * The exported references are taken from the datasource given in the 'datasource'
   * parameter, selected by the 'selector' parameter and formatted with the export format
   * given in the 'format' parameter. The 'selector' has the following syntax:
   *
   * folder: int folder id
   * ids: string list of ids, separated by comma
   * query: natual language query
   * @throws \yii\web\RangeNotSatisfiableHttpException
   */
  public function actionIndex()
  {
    $request = Yii::$app->request;
    $datasource = $request->get('datasource');
    $format     = $request->get('format');
    $selector   = $request->get('selector');

    $hasType =  preg_match( '/([^:]+)\:(.+)$/', $selector,$matches);
    if( $hasType ){
      $selector_type  = $matches[1];
      $value = $matches[2];
    } else {
      $selector_type  = "ids";
      $value = $selector;
    }
    $exporter = ExportFormat::createExporter($format);
    switch ($selector_type){
      // ids as a comma-separated list
      case "ids":
        $query = $this->findIn($datasource,"reference") ->where(['in','id',explode(',',$value)]);
        break;
      // folder query
      case "folder":
        /** @var Folder $folder */
        $folder = $this->findIn($datasource,"folder")
          ->where(['id' => $value])
          ->one();
        if (!$folder) {
          throw new UserErrorException("Folder #$selector does not exist.");
        }
        $query = $folder->getReferences();
        break;
      case "query":
        $query = $this->createActiveQueryFromNaturalLanguageQuery(
          Datasource::in($datasource,'reference'),
          $datasource,
          $value
        );
        break;
      default:
        throw new UserErrorException(Yii::t('app',"Invalid export request parameters."));
    }

    //
    $response = Yii::$app->response;
    $response->format = \yii\web\Response::FORMAT_RAW;
    $filename = $datasource . '.' . $exporter->extension;

    // todo count results, and use paged batches,
    // todo use streaming? see https://www.yiiframework.com/doc/api/2.0/yii-web-response#sendStreamAsFile()-detail
    // if batch processing is preferred, load result into memory
    //if( $exporter->preferBatch ){
    $data = $exporter->export($query->all());
    $response->sendContentAsFile($data, $filename, [
      'mimeType' => $exporter->mimeType
    ]);
    return;
    //}
  }
}
