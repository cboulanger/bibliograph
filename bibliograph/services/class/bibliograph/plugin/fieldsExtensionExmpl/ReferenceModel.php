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

qcl_import( "bibliograph_model_ReferenceModel" );

/**
 * Default reference model
 */
class bibliograph_plugin_fieldsExtensionExmpl_ReferenceModel
  extends bibliograph_model_ReferenceModel
{

  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  /**
   * model properties
   */
  private $properties = array(

    '_category' => array(
        'check'    => "string",
        'sqltype'  => "varchar(100)"
    ),
    '_owner' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
    '_source' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
    '_sponsor' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
    '_date_ordered' => array(
        'check'    => "string",
        'sqltype'  => "date"
    ),
    '_date_received' => array(
        'check'    => "string",
        'sqltype'  => "date"
    ),
    '_date_reimbursement_requested' => array(
        'check'    => "string",
        'sqltype'  => "date"
    ),
    '_inventory' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    )
 );

  //-------------------------------------------------------------
  // Init
  //-------------------------------------------------------------

  function __construct( $datasourceModel )
  {
    parent::__construct( $datasourceModel );
    $this->addProperties( $this->properties );
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Returns the schema object used by this model
   * @return bibliograph_plugin_fieldsExtensionExmpl_Schema
   */
	function getSchemaModel()
	{
	  static $schemaModel = null;
	  if ( $schemaModel === null)
	  {
	    qcl_import( "bibliograph_plugin_fieldsExtensionExmpl_Schema" );
	    $schemaModel = new bibliograph_plugin_fieldsExtensionExmpl_Schema( $this );
	  }
	  return $schemaModel;
	}
}
