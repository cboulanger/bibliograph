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

qcl_import( "qcl_data_datasource_DbModel" );
qcl_import( "qcl_access_model_User" );
qcl_import( "qcl_config_ConfigModel" );

/**
 * model for bibliograph datasources based on an sql database
 */
class qcl_access_DatasourceModel
  extends qcl_data_datasource_DbModel
{

  /**
   * The name of the datasource schema
   * @var string
   */
  protected $schemaName = "qcl.schema.access";

  /**
   * The description of the datasource schema
   * @var string
   */
  protected $description =
    "The schema the qcl datasource supplying the models for access control";

  /**
   * Returns singleton instance of this class. Subclasses must
   * implement this method verbatim
   * @return qcl_access_DatasourceModel
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Overridden to suppress datasource prefix.
   * TODO: access models should use datasource
   */
  public function getTablePrefix()
  {
    return $this->getQueryBehavior()->getTablePrefix();
  }

  /**
   * Initialize the datasource, registers the models
   */
  public function init()
  {
    if ( parent::init() )
    {
      $this->registerModels( array(
        'user'        => array( 'class' => "qcl_access_model_User" ),
        'permission'  => array( 'class' => "qcl_access_model_Permission" ),
        'role'        => array( 'class' => "qcl_access_model_Role" ),
        'group'       => array( 'class' => "qcl_access_model_Group" ),
        'session'     => array( 'class' => "qcl_access_model_Session" ),
        'config'      => array( 'class' => "qcl_config_ConfigModel" ),
        'userConfig'  => array( 'class' => "qcl_config_UserConfigModel" )
      ) );
    }
  }

  /**
   * Getter for schema name
   * @throws LogicException
   * @return string
   */
  public function getSchemaName()
  {
    if ( $this->schemaName == "qcl.schema.access" and $this->className() !== __CLASS__ )
    {
      throw new LogicException( sprintf(
        "You need to define the protected 'schemaName' property in class %s",
        $this->className()
      ) );
    }
    return $this->schemaName;
  }

  /**
   * Returns the user model
   * @return qcl_access_model_User
   */
  public function getUserModel()
  {
    return $this->getInstanceOfType("user");
  }

  /**
   * Returns the permission model
   * @return qcl_access_model_Permission
   */
  public function getPermissionModel()
  {
    return $this->getInstanceOfType("permission");
  }

  /**
   * Returns the role model
   * @return qcl_access_model_Role
   */
  public function getRoleModel()
  {
    return $this->getInstanceOfType("role");
  }

  /**
   * Returns the role model
   * @return qcl_access_model_Group
   */
  public function getGroupModel()
  {
    return $this->getInstanceOfType("group");
  }

  /**
   * Returns the session model
   * @return qcl_access_model_Session
   */
  public function getSessionModel()
  {
    return $this->getInstanceOfType("session");
  }

  /**
   * Returns the config model
   * @return qcl_config_ConfigModel
   */
  public function getConfigModel()
  {
    return $this->getInstanceOfType("config");
  }

  /**
   * Returns the user config model
   * @return qcl_config_UserConfigModel
   */
  public function getUserConfigModel()
  {
    return $this->getInstanceOfType("userConfig");
  }
}
?>