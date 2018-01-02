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

namespace lib\components;

use Yii;

/**
 *
 */
class Utils extends \yii\base\Component
{
  // function __construct($config){
  //   parent::__construct();
  // }

  /**
   * returns the version of the application
   * @return string
   */
  public function version()
  {
    return trim(file_get_contents(Yii::getAlias('@app/../version.txt')));
  }
  
 /**
   * Returns the URL that sets the application into a specific state, showing a reference and selecting a folder
   * @param string $datasource
   * @param int $folderId
   * @param int $modelId
   */
  public function getAppStateUrl($datasource,$folderId,$modelId)
  {
    //https://demo.bibliograph.org/bibliograph/source/#datasource.database1!modelType.reference!itemView.referenceEditor!folderId.3
    return dirname(dirname(qcl_server_Server::getUrl() ) ) .
      "/build/#datasource.$datasource" .
      ( $folderId ? "!folderId.$folderId" : "" ).
      ( $modelId  ? "!modelType.reference!modelId.$modelId" : "");
  }    

  //-------------------------------------------------------------
  // ini values
  //-------------------------------------------------------------


  /**
   * Returns a configuration value of the pattern "foo.bar.baz"
   * This retrieves the values set in the service.ini.php file.
   */
  public function getIniValue( $path )
  {
    static $ini = null;
    if( is_null($ini) ){
      $ini = require(Yii::getAlias('@app/config/ini.php'));
    }
    $parts = explode(".",$path);
    // drill into ini array
    $value = $ini;
    while( is_array($value) and $part = array_shift($parts) ){
      if ( isset( $value[$part] ) ) {
        $value = $value[$part];
        continue;
      }
      throw new InvalidArgumentException("No ini value for '$path' exists.");
    }
    // post-process value
    if( $value == "on" or $value == "yes" )
    {
      $value = true;
    }
    elseif ( $value == "off" or $value == "no" )
    {
      $value = false;
    }
    return $value;
  }

  /**
   * Returns an array of values corresponding to the given array of keys from the
   * initialization configuration data.
   * @param array $arr
   * @return array
   */
  public function getIniValues( $arr )
  {
    return array_map( function($elem) {
      return $this->getIniValue( $elem );
    }, $arr );
  }

  //-------------------------------------------------------------
  // initial data
  //-------------------------------------------------------------

  /**
   * Imports initial data
   * @param array $data
   *    Map of model types and paths to the xml data files
   * @param qcl_access_DatasourceModel $accessDatasource
   *    Optional. If not given, qcl_access_DatasourceModel is used.
   *    You can provide a subclass of qcl_access_DatasourceModel which
   *    selectively override the used model types in the init() method by
   *    using the registerModels() method and a map of the models to
   *    override.
   * @throws InvalidArgumentException
   * @see qcl_access_DatasourceModel::init()
   */
  protected function importInitialData( $data, $accessDatasource=null )
  {

    if ( $accessDatasource === null )
    {
      qcl_import( "qcl_access_DatasourceModel" );
      $accessDatasource = qcl_access_DatasourceModel::getInstance();
    }
    else
    {
      if ( ! $accessDatasource instanceof qcl_access_DatasourceModel )
      {
        throw new InvalidArgumentException( "The accessDatasource parameter must be an instance of a class inheriting from qcl_access_DatasourceModel");
      }
    }

    /*
     * Register the access models as a datasource to make
     * them accessible to client queries
     */
    try
    {
      $this->log( "Registering access datasource schema" , QCL_LOG_APPLICATION );
      $accessDatasource->registerSchema();
    }
    catch( qcl_data_model_RecordExistsException $e ){}

    /*
     * create datasources
     */
    $dsManager = qcl_data_datasource_Manager::getInstance();
    try
    {
      $this->log( "Creating datasource named 'access'." , QCL_LOG_APPLICATION );
      $dsManager->createDatasource(
        "access","qcl.schema.access", array(
          'hidden' => true
        )
      );
    }
    catch( qcl_data_model_RecordExistsException $e ){}

    /*
     * Import data
     */
    foreach( $data as $type => $path )
    {
      $this->log( "Importing '$type' data...'." , QCL_LOG_APPLICATION );

      /*
       * get model from datasource
       */
      $dsModel = $dsManager->getDatasourceModelByName( "access" );
      $model   = $dsModel->getInstanceOfType( $type );

      /*
       * delete all data
       * @todo check overwrite
       */
      $model->deleteAll();

      /*
       * import new data
       */
      $xmlFile = new qcl_io_filesystem_local_File( "file://" . $path );
      $this->log( "     ... from $path" , QCL_LOG_APPLICATION );
      $model->import( new qcl_data_model_import_Xml( $xmlFile ) );
    }
  }

  //-------------------------------------------------------------
  // etc
  //-------------------------------------------------------------

  /**
   * Returns the url of the client application's build directory
   * @return string
   */
  public function getClientUrl()
  {
    return "http://" . $_SERVER["HTTP_HOST"] .
      dirname( dirname( $_SERVER["SCRIPT_NAME"] ) ) .
      "/build";
  }

  /**
   * Alias of qcl_server_Server::getUrl()
   * @return string
   */
  public function getServerUrl()
  {
    return qcl_server_Server::getUrl();
  }

}
