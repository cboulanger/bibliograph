<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "database1_join_Folder_Reference".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property integer $FolderId
 * @property integer $ReferenceId
 */
class Folder_Reference extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'database1_join_Folder_Reference';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
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
