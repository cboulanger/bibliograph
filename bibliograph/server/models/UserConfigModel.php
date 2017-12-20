<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */
qcl_import( "qcl_data_model_db_ActiveRecord" );

/**
 * Configuration management class, using a database backend
 *
 */
class qcl_config_UserConfigModel
  extends qcl_data_model_db_ActiveRecord
{

  /**
   * The table storing model data
   */
  protected $tableName = "data_UserConfig";

  /**
   * Properties
   */
  private $properties = array(
    'value'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(255)",
      'nullable'  => true
    )

  );

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "UserConfigId";

  /**
   * Relations
   */
  private $relations = array(
    'User_UserConfig' => array(
      'type'        => QCL_RELATIONS_HAS_ONE,
      'target'      => array( 'class' => "qcl_access_model_User" )
    ),
    'Config_UserConfig' => array(
      'type'        => QCL_RELATIONS_HAS_ONE,
      'target'      => array( 'class' => "qcl_config_ConfigModel" )
    ),
  );


  /**
   * Constructor
   */
  function __construct()
  {
    $this->addRelations( $this->relations, __CLASS__ );
    $this->addProperties( $this->properties );
    parent::__construct();
  }

  /**
   * Returns singleton instance.
   * @return qcl_config_UserConfigModel
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }
}
