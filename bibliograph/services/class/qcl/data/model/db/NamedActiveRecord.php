<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_data_model_AbstractNamedActiveRecord" );
qcl_import( "qcl_data_model_db_PropertyBehavior" );
qcl_import( "qcl_data_model_db_QueryBehavior" );
qcl_import( "qcl_data_model_db_RelationBehavior" );

/**
 * Abstrac class for models that are based on a relational
 * database.
 */
class qcl_data_model_db_NamedActiveRecord
  extends qcl_data_model_AbstractNamedActiveRecord
{

  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  /**
   * Property information for the property behavior. Similar to the
   * qooxdoo property definition syntax, with some additional features.
   * @see qcl_data_model_PropertyBehavior
   * @var array
   */
  private $properties = array(
    "id" => array(
      "check"    => "integer",
      "export"   => false
    ),
    "namedId" => array(
      "check"    => "string",
      "sqltype"  => "varchar(50)",
      "unique"   => true
    ),
    "created" => array(
      "check"    => "qcl_data_db_Timestamp",
      "sqltype"  => "timestamp",
      "nullable" => true,
      "init"     => null,
      "export"   => false
    ),
    "modified" => array(
      "check"    => "qcl_data_db_Timestamp",
      "sqltype"  => "current_timestamp",
      "nullable" => true,
      "init"     => null,
      "export"   => false
    )
  );

  /**
   * The name of the table that this model stores its data in.
   * If you don't provide a name here, the name of the class is
   * used.
   * @var string
   */
  protected $tableName;

  /**
   * Property behavior object. Access with getPropertyBehavior()
   * @var qcl_data_model_db_PropertyBehavior
   */
  private $propertyBehavior;

  /**
   * The query behavior object. Access with getQueryBehavior()
   * @var qcl_data_model_db_QueryBehavior
   */
  private $queryBehavior;

  /**
   * The relation behavior object. Access with getRelationBehavior()
   * @var qcl_data_model_db_RelationBehavior
   */
  private $relationBehavior;

  //-------------------------------------------------------------
  // Initialization
  //-------------------------------------------------------------

  /**
   * Constructor
   * @param qcl_data_datasource_DbModel|null $datasourceModel Optional datasource
   *  model which provides shared resources for several models that belong
   *  to it.
   */
  function __construct( $datasourceModel = null )
  {
    parent::__construct( $datasourceModel );
    $this->addProperties( $this->properties );
  }

  //-------------------------------------------------------------
  // getters and setters
  //-------------------------------------------------------------

  /**
   * Getter for table name. If no name is set, use class name
   * as table name
   * @return string
   */
  public function tableName()
  {
    return $this->tableName;
  }

  //-------------------------------------------------------------
  // Behaviours
  //-------------------------------------------------------------

  /**
   * Returns the behavior object responsible for maintaining the object
   * properties and providing access to them.
   * @override
   * @return qcl_data_model_db_PropertyBehavior
   */
  public function getPropertyBehavior()
  {
    if ( $this->propertyBehavior === null )
    {
      $this->propertyBehavior = new qcl_data_model_db_PropertyBehavior( $this );
    }
    return $this->propertyBehavior;
  }

  /**
   * Returns the query behavior.
   * @return qcl_data_model_db_QueryBehavior
   */
  public function getQueryBehavior()
  {
    if ( $this->queryBehavior === null )
    {
      $this->queryBehavior = new qcl_data_model_db_QueryBehavior( $this );
    }
    return $this->queryBehavior;
  }

  /**
   * Returns the relation behavior.
   * @return qcl_data_model_db_RelationBehavior
   */
  public function getRelationBehavior()
  {
    if ( $this->relationBehavior === null )
    {
      $this->relationBehavior = new qcl_data_model_db_RelationBehavior( $this );
    }
    return $this->relationBehavior;
  }

}
?>