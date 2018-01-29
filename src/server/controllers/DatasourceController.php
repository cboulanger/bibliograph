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

use app\controllers\AppController;


/**
 * Service class providing methods to work with datasources.
 */
class DatasourceController extends AppController
{
  /**
   * Creates and returns a dasource with the given name, of the default type that the
   * application supports
   * @param $namedId
   * @param array $data
   * @return \qcl_data_datasource_DbModel 
   */
  public function createDatasource( $namedId, $data= array() )
  {
    
    $mgr = qcl_data_datasource_Manager::getInstance();
    if ( ! isset( $data['dsn'] ) )
    {
      $data['dsn'] = $this->getUserDsn();
    }
    if ( ! isset( $data['parentId'] ) )
    {
      $data['parentId'] =0;
    } 
    $datasourceModel = $mgr->createDatasource( $namedId, $this->defaultSchema(), $data );

    /*
     * create config keys for the datasource
     * @todo generalize this
     * @todo check that setup calls this
     */
    $configModel = $this->getApplication()->getConfigModel();
    $key = "datasource.$namedId.fields.exclude";
    $configModel->createKeyIfNotExists( $key, QCL_CONFIG_TYPE_LIST, false, array() );

    return $datasourceModel;
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