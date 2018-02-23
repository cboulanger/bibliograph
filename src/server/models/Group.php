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

use lib\models\BaseModel;
use app\models\User;
use app\models\Datasource;

use app\models\Group_User;
use app\models\Datasource_Group;

/**
 * This is the model class for table "data_Group".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property string $description
 * @property integer $ldap
 * @property string $defaultRole
 * @property integer $active
 */
class Group extends BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Group';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['ldap', 'active'], 'integer'],
      [['namedId'], 'string', 'max' => 50],
      [['name', 'description'], 'string', 'max' => 100],
      [['defaultRole'], 'string', 'max' => 30],
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
      'namedId' => Yii::t('app', 'Group ID'),
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'name' => Yii::t('app', 'Name'),
      'description' => Yii::t('app', 'Description'),
      'ldap' => Yii::t('app', 'Ldap'),
      'defaultRole' => Yii::t('app', 'Default Role'),
      'active' => Yii::t('app', 'Active'),
    ];
  }


  public function getFormData()
  {
    return [
      'namedId'     => [],
      'name'        => [],
      'description' => [],
      'defaultRole' => [
        'type'        => "selectbox",
        'label'       => Yii::t('app',"Default role for new users"),
        'delegate'    => [
          'options'     => "getDefaultRoleListData"
        ]
      ]
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * @return \yii\db\ActiveQuery
   */           
  protected function getGroupUsers()
  {
    return $this->hasMany(Group_User::className(), ['GroupId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */           
  public function getUsers()
  {
    return $this->hasMany(User::className(), ['id' => 'UserId'])->via('groupUsers');
  }

  /**
   * @return \yii\db\ActiveQuery
   */           
  protected function getGroupDatasources()
  {
    return $this->hasMany(Datasource_Group::className(), ['GroupId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */           
  public function getDatasources()
  {
    return $this->hasMany(Datasource::className(), ['id' => 'DatasourceId'])->via('groupDatasources');
  }

  /**
   * Returns the usernames of the members of the group
   * @return string[]
   */           
  public function getUserNames()
  {
    $result = $this->getUsers()->all();
    if( is_null( $result ) ) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  } 

  /**
   * Returns the names of the datasources that are accessible to this groupo
   */
  public function getDatasourceNames()
  {
    $result = $this->getDatasources()->all();
    if( is_null( $result ) ) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  }

  /**
   * Returns data for a select box with the role names
   *
   * @return array
   */
  public function getDefaultRoleListData()
  {
    $listData = Role::find()
      ->select("name as label, namedId as value")
      ->orderBy("name")
      ->asArray()
      ->all();
    array_unshift( $listData, [
      'label' => Yii::t('app',"No role"),
      'value' => ""
    ]);
    return $listData;
  }
}
