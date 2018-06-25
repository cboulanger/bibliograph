<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\modules\converters\controllers;
use app\controllers\AppController;
use app\models\ExportFormat;
use lib\dialog\Form;
use lib\dialog\Popup;
use Yii;

class ExportController extends AppController
{

  /**
   * Returns a dialog to export references
   * @param string $datasource
   * @param string $selector
   * @return string Diagnostic message
   */
  public function actionFormatDialog( string $datasource, string $selector )
  {
     Form::create(
       Yii::t('app', "Choose the export format"),
      [
        'format'  => [
          'label'   => "",
          'type'    => "selectbox",
          'options' => $this->listData()
        ]
      ],
      true, // allow cancel
       /*Yii::$app->controller->route*/ "converters/export",
      "handle-dialog-response",
      [$datasource, $selector],
      [
        'width' => 500,
        'caption' => Yii::t('app', "Export references")
      ]
    );
     return "Created export dialog.";
  }

  /**
   * Returns qx.ui.form.List compatible data with the registered
   * export formats
   *
   * @return array
   */
  protected function listData()
  {
    return ExportFormat::find()
      ->select("name as label, namedId as value")
      ->orderBy('name')
      ->asArray()
      ->all();
  }

  /**
   * Handles the dialog response.
   * @param $data
   * @param string $datasource
   * @param string $selector
   * @return string Diagnostic message
   */
  public function actionHandleDialogResponse( $data=null, string $datasource=null, string $selector=null )
  {
    if ( $data === null ) {
      return "Dialog was cancelled.";
    }
    Popup::create(
      Yii::t('app',"Preparing export data. Please wait..."),
      /*Yii::$app->controller->route*/ "converters/export", "start-export",
      [$this->shelve($data, $datasource, $selector)]
    );
    return "Created message to show popup.";
  }

  /**
   * Service to create a file with the export data for download by the client.
   * Dispatches a message which will trigger the download
   * @param mixed $dummy Default response parameter from dialog widget, can be discarded
   * @param $shelfId
   * @return string Diagnostic message
   */
  public function actionStartExport( $dummy, $shelfId )
  {
    $shelfData = $this->unshelve( $shelfId );
    list( $data, $datasource, $selector ) = $shelfData;
    // todo: Use yii\helpers\Url
    $url  = Yii::$app->homeUrl .
      '?r=converters/download' .
      '&auth_token=' . Yii::$app->user->getIdentity()->getAuthKey() .
      '&format=' . $data->format .
      '&datasource=' . $datasource .
      '&selector=' . $selector;
    Popup::create(""); // hide the popup
    $this->dispatchClientMessage("window.location.replace", array(
      'url' => $url
    ) );
    return "Sent message to start download";
  }
}
