<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "database1_join_Folder_Reference".
 *
 * @property integer $FolderId
 * @property integer $ReferenceId
 */
class Folder_Reference extends \lib\models\BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return '{{%join_Folder_Reference}}';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
    [['FolderId', 'ReferenceId'], 'integer'],
    [['FolderId', 'ReferenceId'], 'unique', 'targetAttribute' => ['FolderId', 'ReferenceId'], 'message' => 'The combination of Folder ID and Reference ID has already been taken.'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => 'ID',
      'created' => 'Created',
      'modified' => 'Modified',
      'FolderId' => 'Folder ID',
      'ReferenceId' => 'Reference ID',
    ];
  }
}
