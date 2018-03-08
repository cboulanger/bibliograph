<?php

namespace app\modules\z3950\controllers;

use yii\web\Controller;
use app\modules\z3950\lib\yaz\Yaz;

/**
 * Default controller for the `z3950` module
 */
class DefaultController extends Controller
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

  /*
   ---------------------------------------------------------------------------
      TABLE INTERFACE API
   ---------------------------------------------------------------------------
   */

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
   * Returns the query as a string constructed from the
   * query data object
   * @param object $queryData
   * @return string
   */
  protected function getQueryString( $queryData )
  {
    $query = $queryData->query->cql;
    return $this->fixQueryString($query);
  }

  /**
   * Checks the query and optimizes it before
   * sending it to the remote server
   * @param $query
   * @return string
   */
  protected function fixQueryString( $query )
  {
    // todo: identify DOI
    if( substr( $query, 0, 3 ) == "978")
    {
      $query = 'isbn=' . $query;
    }
    elseif ( ! strstr( $query, "=" ) )
    {
      $query = 'all="' . $query . '"';
    }
    return $query;
  }

  /**
   * Configures the yaz object for a ccl query with a minimal common set of fields:
   * title, author, keywords, year, isbn, all
   * @param YAZ $yaz
   * @return void
   */
  protected function configureCcl( YAZ $yaz )
  {
    $yaz->ccl_configure(array(
      "title"     => "1=4",
      "author"    => "1=1004",
      "keywords"  => "1=21",
      "year"      => "1=31",
      "isbn"      => "1=7",
      "all"       => "1=1016"
    ) );
  }

  /**
   * Service method that returns ListItem model data on the available library servers
   * @param boolean $all
   *      Whether to return only the active datasources (default) or all datasource
   * @param boolean $reloadFromXmlFiles
   *      Whether to reload the list from the XML Explain files in the filesystem.
   *      This is neccessary if xml files have been added or removed.
   */
  public function actionServerListItems($activeOnly=true,$reloadFromXmlFiles=false)
  {
    // Reset list of Datasources
    if ( $reloadFromXmlFiles )
    {
      z3950_DatasourceModel::getInstance()->createFromExplainFiles();
    }

    // Return list of Datasources
    $listItemData = array();
    $lastDatasource = $this->getApplication()->getPreference("z3950.lastDatasource");
    $dsModel = z3950_DatasourceModel::getInstance();
    $dsModel->findAll();
    while( $dsModel->loadNext() )
    {
      // clear cache
      try
      {
        $dsModel->getInstanceOfType("record")->deleteAll();
        $dsModel->getInstanceOfType("search")->deleteAll();
        $dsModel->getInstanceOfType("result")->deleteAll();
      }
      catch( PDOException $e) {} // FIXME This should not be a PDOException, see https://github.com/cboulanger/bibliograph/issues/133

      // assemble data
      if( $activeOnly and ! $dsModel->getActive() ) continue;

      $name   = $dsModel->getName();
      $value  = $dsModel->getNamedId();
      $listItemData[] = array(
        'label'     => $name,
        'value'     => $value,
        'active'    => $dsModel->getActive(),
        'selected'  => $value == $lastDatasource
      );
    }
    return $listItemData;
  }
}
