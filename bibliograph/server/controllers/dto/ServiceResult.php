<?php

namespace app\controllers\dto;

use JsonRpc2\Dto;

class ServiceResult extends Dto
{
  /** @var string */
  public $type;

  /** @var mixed */
  public $data;

  /** @var array */
  public $events = [];

  /** the property cache */
  protected $_data = [];

  /**
   * Magic setter method. Stores the property value in a cache for 
   * later validation. 
   *
   * @param string $key
   * @param mixed $value
   */
  public function __set( $key, $value ){
    $this->_data[$key] = $value;
  }

  public function validate(){
    $this->setDataFromArray($this->_data);
  }

  /**
   * Add an event to the event queue of this DTO.   
   *
   * @param string $name
   * @param mixed $data defaults to true
   * @return void
   */
  public function addEvent( $name, $data=true ){
    $this->events[] = [ 'name' => $name, 'data' => $data ];
  }

}