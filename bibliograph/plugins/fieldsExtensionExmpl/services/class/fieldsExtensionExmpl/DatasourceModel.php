<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

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
class fieldsExtensionExmpl_DatasourceModel
  extends bibliograph_model_AbstractDatasourceModel
{
  /**
   * The name of the datasource schema
   * @var string
   */
  protected $schemaName = "bibliograph.schema.fieldsExtensionExmpl";

  /**
   * The description of the datasource schema
   * @var string
   */
  protected $description =
    "Datasource model for the fieldsExtensionExmpl plugin";


 /**
   * Overriding schema property
   */
  private $properties = array(
    'schema' => array(
      'nullable'  => false,
      'init'      => "fieldsExtensionExmpl"
    )
  );

  /**
   * Constructor, overrides some properties
   * @return \fieldsExtensionExmpl_DatasourceModel
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
  }

  /**
   * Returns singleton instance of this class.
   * @return fieldsExtensionExmpl_DatasourceModel
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * FIXME here and in other datasource models
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
          'class'       => "fieldsExtensionExmpl_ReferenceModel",
          'replace'     => "bibliograph_model_ReferenceModel"
        ),
        'folder'    => array(
          'class'       => "fieldsExtensionExmpl_FolderModel",
          'replace'     => "bibliograph_model_FolderModel"
        ),
      ) );
    }
  }
}
