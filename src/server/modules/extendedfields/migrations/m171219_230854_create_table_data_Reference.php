<?php

namespace app\modules\extendedfields\migrations;

use app\migrations\schema\bibliograph_datasource\{
  m171219_230854_create_table_data_Reference as create_table_data_Reference
};

class m171219_230854_create_table_data_Reference extends create_table_data_Reference
{
  public function getSchema()
  {
    return array_merge(parent::getSchema(), [
      '_category' => $this->string(100),
      '_owner' => $this->string(50),
      '_source' => $this->string(255),
      '_sponsor' => $this->string(50),
      '_date_ordered' => $this->date(),
      '_date_received' => $this->date(),
      '_date_reimbursement_requested' => $this->date(),
      '_inventory' => $this->string(50),
    ]);
  }
}
