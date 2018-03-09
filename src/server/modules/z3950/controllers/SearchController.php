<?php

namespace app\modules\z3950\controllers;

use app\controllers\AppController;
use app\models\Datasource;
use app\modules\z3950\models\Datasource as Z3950Datasource;
use app\modules\z3950\Module;
use lib\exceptions\UserErrorException;
use app\modules\z3950\lib\yaz\Yaz;
use yii\db\Exception;

/**
 * Default controller for the `z3950` module
 * @property Module $module
 */
class SearchController extends AppController
{
  /**
   * Returns the default model type for which this controller is providing
   * data.
   * @return string
   */
  protected function getModelType()
  {
    return "record";
  }



  //---------------------------------------------------------------------------
  //  ACTIONS
  //---------------------------------------------------------------------------


  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   * @param null|string $modelClassType
   */
  public function actionTableLayout($datasource, $modelClassType = null)
  {
    return array(
      'columnLayout' => array(
        'id' => array(
          'header'  => "ID",
          'width'   => 50,
          'visible' => false
        ),
        'author' => array(
          'header'  => _("Author"),
          'width'   => "1*"
        ),
        'year' => array(
          'header'  => _("Year"),
          'width'   => 50
        ),
        'title' => array(
          'header'  => _("Title"),
          'width'   => "3*"
        )
      ),
      'queryData' => array(
        'link'    => array(),
        'orderBy' => "author,year,title",
      ),
      'addItems' => array()
    );
  }


  /**
   * Service method that returns ListItem model data on the available library servers
   * @param boolean $all
   *      Whether to return only the active datasources (default) or all datasource
   * @param boolean $reloadFromXmlFiles
   *      Whether to reload the list from the XML Explain files in the filesystem.
   *      This is neccessary if xml files have been added or removed.
   * @throws UserErrorException
   */
  public function actionServerListItems($activeOnly=true,$reloadFromXmlFiles=false)
  {
    // Reset list of Datasources
    if ( $reloadFromXmlFiles )
    {
      try {
        $this->module->createDatasources();
      } catch (\Throwable $e) {
        throw new UserErrorException($e->getMessage(),null, $e);
      }
    }
    // Return list of Datasources
    $listItemData = array();
    $lastDatasource = $this->module->getPreference("lastDatasource");
    foreach (Datasource::findBySchema("z3950") as $datasource)
    {
      if( $activeOnly and ! $datasource->active ) continue;
      $listItemData[] = array(
        'label'     => $datasource->title,
        'value'     => $datasource->namedId,
        'active'    => $datasource->active,
        'selected'  => $datasource->namedId == $lastDatasource
      );
    }
    return $listItemData;
  }

  /**
   * Sets datasources active / inactive, so that they do not show up in the
   * list of servers
   * param array $map Maps datasource ids to status
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   * @todo add DTO
   */
  public function actionSetDatasourceState( $map )
  {
    $this->requirePermission("z3950.manage");
    foreach( (array) $map as $namedId => $active )
    {
      $datasource = Z3950Datasource::findByNamedId($namedId);
      $datasource->active= (int) $active;
      try {
        $datasource->save();
      } catch (Exception $e) {
        throw new UserErrorException($e->getMessage(),$e->getCode(),$e);
      }
    }
    $this->broadcastClientMessage("z3950.reloadDatasources");
    return "OK";
  }




}
