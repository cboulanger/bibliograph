<?php

namespace lib\util;

use Yii;

class Convert 
{

  public static function arrayToObject( array $data )
  {
    return json_decode( json_encode( $data ), false );
  }
  
  public static function objectToArray( stdClass $data )
  {
    return json_decode( json_encode( $data ), true );
  }
}