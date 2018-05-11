<?php

namespace app\modules\webservices;
use yii\base\BaseObject;

/**
 * Class AbstractConnector
 * @package modules\webservices
 * @property string $id
 * @property string $name
 * @property string $description
 */
class AbstractConnector extends BaseObject
{
  /**
   * @var string
   */
  protected $id = "";

  /**
   * @var string
   */
  protected $name = "";

  /**
   * @var string
   */
  protected $description = "";

  /**
   * @var array
   */
  protected $searchFields = [];

  /**
   * @return string
   */
  public function getId(){
    return $this->id;
  }

  /**
   * @return string
   */
  public function getName(){
    return $this->name;
  }

  /**
   * @return string
   */
  public function getDescription(){
    return $this->description;
  }

  /**
   * @return array
   */
  public function getSearcFields(){
    return $this->searchFields;
  }

}