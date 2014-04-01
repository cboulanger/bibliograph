<?php

/**
 * Wrapper around php json-extension to provide a unified interface
 * for a JSON encoder/decoder. This allows to use different implementations
 * with the JSONRPC server. Make sure to include your custom implementation
 * before including the server code.
 */
class JsonWrapper
{
  var $_json = null;

	/**
	 * Forces the use of the slow php-only encoder/decoder, which is
	 * necessary if you want to encode dates.
	 * @return void
	 */
	function useJsonClass()
	{
	  require_once dirname(__FILE__) . "/JSON.phps";
	  $this->_json = new JSON;
	}

	/**
	 * Encode data into JSON
	 * @param $data
	 * @return string
	 */
  function encode ( $data )
	{
		if ( $this->_json )
		{
		  return $this->_json->encode( $data );
		}
	  return json_encode( $data );
	}

	/**
	 * Decode a JSON string into a PHP data structure.
	 * @param $string
	 * @return mixed
	 */
	function decode ( $string )
	{
	  if ( $this->_json )
    {
      return $this->_json->decode( $string );
    }
    else
    {
      return json_decode ( $string );
    }
	}
}
?>