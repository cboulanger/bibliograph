<?php
namespace app\migrations\schema;
use yii\db\Migration;

class m171219_230853_create_table_data_DatasourceSchema extends Migration
{
    public function safeUp()
    {
      // table is no longer needed
      return true;
    }

    public function safeDown()
    {
        // do nothing
      return true;
    }
}
