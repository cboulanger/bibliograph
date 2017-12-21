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
use yii\db\ActiveRecord;
use yii\helpers\StringHelper;

class BaseModel extends ActiveRecord
{
    /**
     * The name of the datasource the model is attached to.
     * the "datasource" in bibliograph parlance refers to a named collection
     * of models within a database
     */
    public static $datasource = "";

    /**
     * The name of the database component
     */
    public static $database = "db";

    /**
     * Returns the database object used by the model
     */
    public static function getDb()
    {
        return \Yii::$app->{self::$database};
    }

    /**
     * Returns the name of the table, based on the datasource
     */
    public static function tableName()
    {
        $parts = [];
        if (self::$datasource) {
            $parts[] = self::$datasource;
        }
        $parts[] = "data";
        $parts[] = StringHelper::basename(get_called_class());
        return implode("_", $parts );
    }

    /**
     * Shim method
     *
     * @param [type] $string
     * @return void
     */
    protected function tr($string)
    {
        return Yii::t('app', $string );
    }
}
