<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 05.06.18
 * Time: 08:26
 */

namespace lib\models;

use yii\db\ActiveRecord;

/**
 * This class models a migration table
 * @package lib\models
 * @property string $version
 * @property string $apply_time
 */
class Migration extends ActiveRecord
{
  /**
   * Read-only
   * @var string
   */
  public $version;

  /**
   * Read-only
   * @var string
   */
  public $apply_time;

  /**
   * @return string
   */
  public function getVersion(){
    return $this->version;
  }

  public function getApply_Time(){
    return $this->apply_time;
  }
}