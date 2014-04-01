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

qcl_import( "qcl_data_model_db_NamedActiveRecord" );
qcl_import( "qcl_data_datasource_DbModel" );

/**
 * Role class
 */
class qcl_access_model_Role
  extends qcl_data_model_db_NamedActiveRecord
{

  /**
   * The table storing model data
   */
  protected $tableName = "data_Role";

  /**
   * Properties
   */
  private $properties = array(
    'name'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    ),
    'description'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    ),
    'active'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'nullable'  => false,
      'init'      => false
    )
  );

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "RoleId";

  /**
   * Relations
   */
  private $relations = array(
    'Permission_Role' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_access_model_Permission" )
    ),
    'User_Role' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_access_model_User" )
    ),
    'Datasource_Role' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_data_datasource_DbModel" )
    )
  );



  /**
   * Constructor
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );

    $this->formData = array(
      'name'        => array(
        'label'       => $this->tr("Name")
      ),
      'description' => array(
        'label'       => $this->tr("Description")
      )
    );
  }

  /**
   * Returns singleton instance.
   * @static
   * @return qcl_access_model_Role
   */
  public static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  /**
   * Getter for permission model instance
   * @return qcl_access_model_Permission
   */
  protected function getPermissionModel()
  {
    return $this->getRelationBehavior()->getTargetModel("Permission_Role");
  }

  /**
   * Getter for user model instance
   * @return qcl_access_model_User
   */
  protected function getUserModel()
  {
    return $this->getRelationBehavior()->getTargetModel("User_Role");
  }

  /**
   * Returns a list of permissions connected to the current model record.
   * @return array
   */
  public function permissions()
  {
    $permModel = $this->getPermissionModel();
    try
    {
      $permModel->findLinked( $this );
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      return array();
    }
    $permissions =  array();
    while ( $permModel->loadNext() )
    {
      $permissions[] = $permModel->namedId();
    }
    return $permissions;
  }

  /**
   * Returns a list of users connected to the current model record.
   * @return array
   */
  public function users()
  {
    $userModel = $this->getUserModel();
    $userModel->findLinked( $this );
    $users =  array();
    while ( $userModel->loadNext() )
    {
      $users[] = $userModel->namedId();
    }
    return $users;
  }
}
?>