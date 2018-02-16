<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use Yii;
use app\controllers\AppController;
use app\models\Datasource;

/**
 * Service class providing methods to work with datasources.
 */
class DatasourceController extends AppController
{
  /**
   * Creates a dasource with the given name, of the default type that the
   * application supports
   * @param string $namedId
   * @param string $namedId
   */
  public function actionCreate( $namedId, $type=null )
  {
    $this->requirePermission("datasource.create");
    // @todo handle type
    // @tod validate input
    $datasource = Datasource::create($namedId);
    $datasource->createModelTables();
    Yii::$app->config->addPreference( "datasource.$namedId.fields.exclude", []);
    return "Datasource '$namedId' has been created";
  }

  /**
   * Return the model for the datasource store
   *
   */
  public function actionLoad(){
    $activeUser = $this->getActiveUser();
    //$datasources = $activeUser->getDatasources() //@todo
    $datasources = \app\models\Datasource::find()
      ->select(['namedId AS value','title AS label','title','description'])
      ->where(['schema' => 'bibliograph.schema.bibliograph2'])
      ->andWhere(['active' => 1])
      ->andWhere(['hidden' => 0])
      ->asArray()->all();
    return $datasources;
  }

}