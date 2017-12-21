<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\models;

use Yii;

use app\models\BaseModel;
use app\models\Group;
use app\models\Permissions;
use app\models\Roles;
use app\models\User_Role;


/**
 * This is the model class for table "data_User".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property string $password
 * @property string $email
 * @property integer $anonymous
 * @property integer $ldap
 * @property integer $active
 * @property string $lastAction
 * @property integer $confirmed
 * @property integer $online
 */
class User extends BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_User';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified', 'lastAction'], 'safe'],
      [['anonymous', 'ldap', 'active', 'confirmed', 'online'], 'integer'],
      [['namedId', 'password'], 'string', 'max' => 50],
      [['name'], 'string', 'max' => 100],
      [['email'], 'string', 'max' => 255],
      [['namedId'], 'unique'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => Yii::t('app', 'ID'),
      'namedId' => Yii::t('app', 'Named ID'),
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'name' => Yii::t('app', 'Name'),
      'password' => Yii::t('app', 'Password'),
      'email' => Yii::t('app', 'Email'),
      'anonymous' => Yii::t('app', 'Anonymous'),
      'ldap' => Yii::t('app', 'Ldap'),
      'active' => Yii::t('app', 'Active'),
      'lastAction' => Yii::t('app', 'Last Action'),
      'confirmed' => Yii::t('app', 'Confirmed'),
      'online' => Yii::t('app', 'Online'),
    ];
  }

  public function getFormData()
  {
    return [
      'name' => array(
        'name' => "name",
        'label' => $this->tr("Full name"),
      ),
      'email' => array(
        'label' => $this->tr("Email address"),
        'placeholder' => $this->tr("Enter a valid Email address"),
        'validation' => array(
          'validator' => "email",
        ),
      ),
      'password' => array(
        'label' => $this->tr("Password"),
        'type' => "PasswordField",
        'value' => "",
        'placeholder' => $this->tr("To change the password, enter new password."),
        'marshaler' => array(
          'unmarshal' => array('callback' => array("this", "checkFormPassword")),
        ),
      ),
      'password2' => array(
        'label' => $this->tr("Repeat password"),
        'type' => "PasswordField",
        'value' => "",
        'ignore' => true,
        'placeholder' => $this->tr("To change the password, repeat new password"),
        'marshaler' => array(
          'unmarshal' => array('callback' => array("this", "checkFormPassword")),
        ),
      ),
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  protected function getUserRoles()
  {
    return $this->hasMany(User_Role::className(), ['UserId' => 'id']);
  }

  protected function getRoles()
  {
    return $this->hasMany(Role::className(), ['id' => 'RoleId'])->via('userRoles');
  }

  protected function getUserGroups()
  {
    return $this->hasMany(Group_User::className(), ['UserId' => 'id']);
  }

  protected function getGroups()
  {
    return $this->hasMany(Group::className(), ['id' => 'GroupId'])->via('userGroups');
  } 

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

}
