<?php
/* ************************************************************************

   qooxdoo dialog library
  
   http://qooxdoo.org/contrib/project#dialog
  
   Copyright:
     2007-2010 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

qcl_import("qcl_data_controller_Controller");


/**
 * Controller that generates model classes from incoming json data.
 */
class qcl_util_devel_ClassCreator
  extends qcl_data_controller_Controller
{
	
	function getType( $value )
	{
		switch ( gettype( $value ) )
		{
			case "NULL":
				return "string";
				
			default:
				return gettype( $value );
		}
	}	

	function getSqltype( $value )
	{
		switch ( gettype( $value ) )
		{
			case "string":
			case "NULL":
				return "varchar(100)";
				
			case "boolean":
				return "tinyint(1)";
				
			case "integer":
				return "int(11)";
				
			default:
				throw new InvalidArgumentException("Invalid data type");
		}
	}

  /**
   * Creates the code for a new model class from the given data
   * @param $classname
   * @param $name
   * @param $data
   * @return string
   */
	function createModelClass( $classname, $name, $data )
	{
		$properties = array();
		foreach( $data as $key => $value)
		{
			$properties[] = "\n/**\n * Enter description here ...\n */\n'$key' => " . 
			var_export( array(
				'check'			=> $this->getType( $value),
				'sqltype'		=> $this->getSqltype( $value ),
				'nullable'	=> true
			), true);
		}
		
		/*
		 * add array stuff and indentation
		 */
		$properties = implode(",\n", $properties );
		$properties = "array(\n" . implode("\n    ",explode("\n", $properties ) ) . "\n  )";
		
		$code = <<<EOF
		
<?php
		
qcl_import( "qcl_data_model_db_ActiveRecord" );

/**
 * Enter description here ...
 */
class $classname
extends qcl_data_model_db_ActiveRecord
{

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  /**
   * The name of the table of this model
   */
  protected \$tableName = "data_{$name}";
  
  /**
   * The foreign key of this model
   */
  protected \$foreignKey = "{$name}Id";  

  /**
   * The model properties
   */
  private \$properties = $properties;

  /**
   * Relations
   */
  private \$relations = array(
    
  );

  /*
  *****************************************************************************
     INITIALIZATION
  *****************************************************************************
  */

  function __construct( \$datasourceModel=null )
  {
    parent::__construct( \$datasourceModel );
    \$this->addProperties( \$this->properties );
    //\$this->addRelations( \$this->relations, __CLASS__ );

  }

  /*
  *****************************************************************************
     API
  *****************************************************************************
  */

}

EOF;
		$this->info($code);
		return "OK";
	}
}
?>