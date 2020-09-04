<?php

namespace app\models;

use Yii;
use yii\db\Exception;
use yii\db\Expression;
use lib\models\BaseModel;

/**
 * This is the model class for table "data_Session".
 *
 * @property int $id
 * @property string $namedId
 * @property string $parentSessionId
 * @property string $ip
 * @property int $UserId
 */
class Session extends BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Session';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['UserId'], 'integer'],
      [['namedId', 'parentSessionId'], 'string', 'max' => 50],
      [['ip'], 'string', 'max' => 32],
      [['namedId'], 'unique'],
      [['namedId', 'ip'], 'unique', 'targetAttribute' => ['namedId', 'ip'], 'message' => 'The combination of Named ID and Ip has already been taken.'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => 'ID',
      'namedId' => 'Named ID',
      'created' => 'Created',
      'modified' => 'Modified',
      'parentSessionId' => 'Parent Session ID',
      'ip' => 'Ip',
      'UserId' => 'User ID',
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * Returns a yii\db\ActiveQuery to find the user object linked to
   * the session instance
   *
   * @return \yii\db\ActiveQuery
   */
  public function getUser()
  {
    return $this->hasOne(User::class, ['id' => 'UserId']);
  }

  /**
   * Returns a yii\db\ActiveQuery to find the message objects linked to
   * the session instance
   *
   * @return \yii\db\ActiveQuery
   */
  public function getMessages()
  {
    return $this->hasMany(Message::class, ['SessionId' => 'id']);
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Overridden method to update the `modified` property.
   *
   * @return void
   */
  public function touch()
  {
    parent::touch('modified');
  }

  /**
   * Cleans up session list by purging all entries that are older than
   * the given minute interval.
   *
   * @param int $intervalInSeconds The interval in seconds. Defaults to 1 hour
   * @return int Number of messages purged
   */
  public static function cleanup( $intervalInSeconds=60*60 )
  {
    $expression = new Expression('DATE_SUB(NOW(), INTERVAL :seconds SECOND)',['seconds' => $intervalInSeconds ]);
    return static::deleteAll(['<', 'modified', $expression]);
  }
}
