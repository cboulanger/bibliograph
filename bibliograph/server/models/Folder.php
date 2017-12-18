<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "database1_data_Folder".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property integer $parentId
 * @property integer $position
 * @property string $label
 * @property string $type
 * @property string $description
 * @property integer $searchable
 * @property integer $searchfolder
 * @property string $query
 * @property integer $public
 * @property integer $opened
 * @property integer $locked
 * @property string $path
 * @property string $owner
 * @property integer $hidden
 * @property string $createdBy
 * @property integer $markedDeleted
 * @property integer $childCount
 * @property integer $referenceCount
 */
class Folder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'database1_data_Folder';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['parentId', 'position'], 'required'],
            [['parentId', 'position', 'searchable', 'searchfolder', 'public', 'opened', 'locked', 'hidden', 'markedDeleted', 'childCount', 'referenceCount'], 'integer'],
            [['label', 'description', 'path'], 'string', 'max' => 100],
            [['type', 'createdBy'], 'string', 'max' => 20],
            [['query'], 'string', 'max' => 255],
            [['owner'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created' => Yii::t('app', 'Created'),
            'modified' => Yii::t('app', 'Modified'),
            'parentId' => Yii::t('app', 'Parent ID'),
            'position' => Yii::t('app', 'Position'),
            'label' => Yii::t('app', 'Label'),
            'type' => Yii::t('app', 'Type'),
            'description' => Yii::t('app', 'Description'),
            'searchable' => Yii::t('app', 'Searchable'),
            'searchfolder' => Yii::t('app', 'Searchfolder'),
            'query' => Yii::t('app', 'Query'),
            'public' => Yii::t('app', 'Public'),
            'opened' => Yii::t('app', 'Opened'),
            'locked' => Yii::t('app', 'Locked'),
            'path' => Yii::t('app', 'Path'),
            'owner' => Yii::t('app', 'Owner'),
            'hidden' => Yii::t('app', 'Hidden'),
            'createdBy' => Yii::t('app', 'Created By'),
            'markedDeleted' => Yii::t('app', 'Marked Deleted'),
            'childCount' => Yii::t('app', 'Child Count'),
            'referenceCount' => Yii::t('app', 'Reference Count'),
        ];
    }
}
