<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "bibliograph_model_AbstractDatasourceModel" );

/**
 * model for bibliograph datasources based on an sql database
 */
class bibliograph_plugin_fieldsExtensionExmpl2_DatasourceModel
  extends bibliograph_model_AbstractDatasourceModel
{
  /**
   * The name of the datasource schema
   * @var string
   */
  protected $schemaName = "bibliograph.schema.fieldsExtensionExmpl2";

  /**
   * The description of the datasource schema
   * @var string
   */
  protected $description =
    "Datasource model for the field extension example 2";


 /**
   * Overriding schema property
   */
  private $properties = array(
    'schema' => array(
      'nullable'  => false,
      'init'      => "fieldsExtensionExmpl2"
    )
  );

  /**
   * Constructor, overrides some properties
   * @return \bibliograph_plugin_fieldsExtensionExmpl2_DatasourceModel
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
  }

  /**
   * Returns singleton instance of this class.
   * @return bibliograph_plugin_fieldsExtensionExmpl2_DatasourceModel
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * @todo
   * @return string
   */
  public function getTableModelType()
  {
    return "reference";
  }
  
  /**
   * Initialize the datasource, registers the models
   */
  public function init()
  {
    if ( parent::init() )
    {
      $this->registerModels( array(
        'reference' => array(
          'model' => array(
            'class'       => "bibliograph_plugin_fieldsExtensionExmpl2_ReferenceModel",
            'replace'     => "bibliograph_model_ReferenceModel"
          ),
          'controller'  => array(
            'service'     => "bibliograph.reference"
          )
        ),
        'folder'    => array(
          'model' => array(
            'class'       => "bibliograph_plugin_fieldsExtensionExmpl2_FolderModel",
            'replace'     => "bibliograph_model_FolderModel"
          ),
          'controller'  => array(
            'service'     => "bibliograph.folder"
          )
        ),
      ) );
    }
  }
}
?>