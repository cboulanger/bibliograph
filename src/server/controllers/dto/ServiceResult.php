<?php

namespace app\controllers\dto;

use ReflectionProperty;

class ServiceResult extends \JsonRpc2\Dto
{
  /** @var string */
  public $type= "ServiceResult";

  /** @var string */
  public $version= "1.0";

  /** @var string */
  public $data;

  /** @var array */
  public $events = [];

  public function __construct()
  {
      // do nothing
  }
  
  /**
   * Set the result of the service
   *
   * @param mixed $data
   * @return void
   */
  public function setResult( $data ){
    $this->data = $data;
  }

  /**
   * Add an event to the event queue of this DTO.   
   *
   * @param string $name
   * @param mixed $data defaults to true
   * @return void
   */
  public function addEvent( $name, $data=true ){
    assert( !empty($name) and \is_string($name), "Invalid event name '$name'" );
    $this->events[] = [ 'name' => $name, 'data' => $data ];
  }
}