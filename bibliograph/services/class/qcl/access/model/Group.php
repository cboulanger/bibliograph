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

qcl_import( "qcl_data_model_db_NamedActiveRecord" );
qcl_import( "qcl_data_datasource_DbModel" );

/**
 * Group model class. Groups have users as members and groups have access
 * to datasources.
 */
class qcl_access_model_Group
  extends qcl_data_model_db_NamedActiveRecord
{

  /**
   * The table storing model data
   */
  protected $tableName = "data_Group";

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
    'ldap'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1) NOT NULL DEFAULT 0",
      'nullable'  => false,
      'init'      => false
    ),
    'defaultRole'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(30)"
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
  protected $foreignKey = "GroupId";

  /**
   * Relations
   */
  private $relations = array(
    'Group_User' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_access_model_User" )
    ),
    'Datasource_Group' => array(
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
      ),
      'defaultRole'      => array(
        'type'        => "selectbox",
        'label'       => $this->tr("Default role for new users"),
        'delegate'    => array(
          'options'     => "getRoleListData"
        )
      )
    );
  }

  /**
   * Returns singleton instance.
   * @static
   * @return qcl_access_model_Group
   */
  public static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  /**
   * Getter for name property
   * @return string
   */
  public function getName()
  {
    return $this->_get("name");
  }

  /**
   * Getter for description property
   * @return string|null
   */
  public function getDescription()
  {
    return $this->_get("description");
  }

  /**
   * Getter for default role property
   * @return string|null
   */
  public function getDefaultRole()
  {
    return $this->_get("defaultRole");
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

  /**
   * Returns data for a select box with the role names
   *
   * @return array
   */
  public function getRoleListData()
  {
    $listData = array( array(
      'label' => $this->tr("No role"),
      'value' => ""
    ) );
    $roleModel = $this->getApplication()->getAccessController()->getRoleModel();
    $roleModel->findAllOrderBy("name");
    while( $roleModel->loadNext() )
    {
      $listData[] = array(
        'label' => $roleModel->getName(),
        'value' => $roleModel->namedId()
      );
    }
    return $listData;
  }
}
