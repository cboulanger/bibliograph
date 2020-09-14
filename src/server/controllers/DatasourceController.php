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

use app\models\BibliographicDatasource;
use lib\exceptions\UserErrorException;
use Yii;
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
   * @throws \lib\exceptions\Exception
   */
  public function actionCreate( $namedId, $type=null )
  {
    $this->requirePermission("datasource.create");
    // @todo handle type
    // @todo validate input
    try {
      $datasource = Yii::$app->datasourceManager->create($namedId);
      $datasource->createModelTables();
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    return "Datasource '$namedId' has been created";
  }

  /**
   * Return the model for the datasource store
   */
  public function actionLoad(){
    $activeUser = $this->getActiveUser();
    $datasourceNames = $activeUser->getAccessibleDatasourceNames();
    $availableDatasources = [];
    foreach ($datasourceNames as $datasourceId ) {
      /** @var BibliographicDatasource $datasource */
      $datasource = $this->datasource($datasourceId);
      if($datasource->active == 1 and $datasource->hidden == 0){
        $availableDatasources[] = [
          'value' => $datasource->namedId,
          'title' => $datasource->title,
          'label' => $datasource->title,
          'services' => $datasource->services,
          'readOnly' => (boolean) $datasource->readonly
        ];
      }
    }
    usort( $availableDatasources, function($a, $b){
      return strcmp($a['title'], $b['title']);
    });
    return $availableDatasources;
  }
}
