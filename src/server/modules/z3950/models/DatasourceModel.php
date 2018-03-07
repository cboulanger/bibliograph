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

qcl_import("bibliograph_model_AbstractDatasourceModel");

/**
 * Datasource model for z3950 datasources
 *
 * Dependencies:
 * - php_yaz extension
 */
class z3950_DatasourceModel
  extends bibliograph_model_AbstractDatasourceModel
{

  protected $schemaName = "bibliograph.schema.z3950";

  protected $description =
    "Datasource model for Z39.50 Datasources";

  /**
   * Overriding schema property
   */
  private $properties = array(
    'schema' => array(
      'nullable'  => false,
      'init'      => "z3950"
    )
  );

  /**
   * @todo
   * @return string
   */
  public function getTableModelType()
  {
    return "record";
  }

  /**
   * Constructor, overrides some properties
   * @return \z3950_DatasourceModel
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
  }

  /**
   * Returns singleton instance of this class.
   * @return z3950_DatasourceModel
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Initialize the datasource, registers the models
   */
  public function init()
  {
    if ( parent::init() )
    {
      $this->registerModels( array(
        'record' => array(
          'model' => array(
            'class'       => "z3950_RecordModel"
          ),
          'controller' => array(
            'service'   => "z3950.Service"
          )
        ),
        'search'    => array(
          'model' => array(
            'class'       => "z3950_SearchModel"
          )
        ),
        'result'    => array(
          'model' => array(
            'class'       => "z3950_ResultModel"
          )
        ),
      ) );
    }
  }

  /**
   * Returns an associative array, keys being the names of the Z39.50 databases, values the
   * paths to the xml EXPLAIN files
   * @return array
   */
  protected function getExplainFileList()
  {
    static $data=null;
    if( $data === null )
    {
      $data = array();
      foreach( scandir( __DIR__ . "/servers" ) as $file )
      {
        if( $file[0] == "." or get_file_extension($file) != "xml" ) continue;
        $path = __DIR__ . "/servers/$file";
        $explain = simplexml_load_file( $path );
        $serverInfo = $explain->serverInfo;
        $database = (string) $serverInfo->database;
        $data[$database] = $path;
      }
    }

    return $data;
  }

  /**
   * Create bibliograph datasources from Z39.50 explain files
   */
  public function createFromExplainFiles()
  {
    $manager = qcl_data_datasource_Manager::getInstance();
    $dsModel = $manager->getDatasourceModel();
    
    // Deleting old datasources
    $dsModel->findWhere( array( "schema" => "bibliograph.schema.z3950" ) );
    while( $dsModel->loadNext() )
    {
      $this->log("Deleting Z39.50 datasource " . $dsModel->getTitle(), BIBLIOGRAPH_LOG_Z3950);
      $manager->deleteDatasource( $dsModel->namedId() );
    }
    
    // Adding new datasources from XML files
    $dsn = str_replace( "&",";", $this->getApplication()->getIniValue("macros.dsn_tmp"));
    foreach( $this->getExplainFileList() as $database => $filepath )
    {
      $datasource = "z3950_" . $database;
      $explainDoc = simplexml_load_file( $filepath );
      $title = substr( (string) $explainDoc->databaseInfo->title, 0, 100 );
      
      $manager->createDatasource(
        $datasource, $this->getSchemaName(), array(
          'dsn'          => $dsn,
          'title'         => $title,
          'hidden'        => true, // should not show up as selectable datasource
          'resourcepath'  => $filepath
        )
      );
      $this->log("Added Z39.50 datasource '$title'", BIBLIOGRAPH_LOG_Z3950);
    }
  }
}
