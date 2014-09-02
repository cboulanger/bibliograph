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
}
