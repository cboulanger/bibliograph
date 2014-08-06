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

qcl_import("qcl_data_model_db_ActiveRecord");

class bibliograph_plugin_z3950_ResultModel
  extends qcl_data_model_db_ActiveRecord
{

  /**
   * model properties
   */
  private $properties = array(

    'firstRow' => array(
        'check'    => "integer",
        'sqltype'  => "int(11)"
    ),
    'lastRow' => array(
        'check'    => "integer",
        'sqltype'  => "int(11)"
    ),
    'firstRecordId' => array(
        'check'    => "integer",
        'sqltype'  => "int(11)"
    ),
    'lastRecordId' => array(
        'check'    => "integer",
        'sqltype'  => "int(11)"
    )
 );

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "ResultId";

  /**
   * Relations
   */
  private $relations = array(
    'Result_Search' => array(
      'type'        => QCL_RELATIONS_HAS_ONE,
      'target'      => array( 'modelType' => "search" )
    )
  );


  //-------------------------------------------------------------
  // Init
  //-------------------------------------------------------------

  function __construct( $datasourceModel )
  {
    parent::__construct( $datasourceModel );
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
  }
}
