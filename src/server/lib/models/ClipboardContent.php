<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 01.06.18
 * Time: 21:34
 */

namespace lib\models;

/**
 * Class ClipboardContent
 * models an entry on the clipboard of a user
 * @package lib\models
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property string $mime_type
 * @property string $data
 * @property int $UserId
 */
class ClipboardContent extends BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Clipboard';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['id', 'UserId'], 'integer'],
      [['created', 'modified'], 'safe'],
      [['mime_type'], 'string', 'max' => 100]
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => 'ID',
      'UserId' => 'User ID',
      'mime_type' => 'MIME type',
      'data'   => "Data",
      'created' => 'Created',
      'modified' => 'Modified'
    ];
  }

}