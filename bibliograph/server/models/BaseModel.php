<?php

namespace app\models;

use Yii;
use \yii\db\ActiveRecord;
use \yii\helpers\StringHelper;

class BaseModel extends ActiveRecord
{
    /**
     * The name of the datasource the model is attached to
     */
    public static $datasource = "";

    /**
     * Returns the name of the table, based on the datasource
     */
    public static function tableName()
    {
        $parts = [];
        if( self::$datasource ) $parts[] = self::$datasource;
        $parts[] = "data";
        $parts[] = StringHelper::basename(get_called_class());
        return implode("_", $parts );
    }

}