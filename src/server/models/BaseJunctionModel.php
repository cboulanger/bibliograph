<?php

namespace app\models;

use Yii;
use \lib\models\BaseModel;
use \yii\helpers\StringHelper;

class BaseJunctionModel extends BaseModel
{
    /**
     * Returns the name of the table, based on the datasource 
     */
    public static function tableName()
    {
        $parts = [];
        if( self::$datasource ) $parts[] = self::$datasource;
        $parts[] = "join";
        $parts[] = StringHelper::basename(get_called_class());
        return implode("_", $parts );
    }

}