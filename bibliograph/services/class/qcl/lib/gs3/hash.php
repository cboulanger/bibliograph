<?php
/**
 * Class to calculate RFC 2104 compliant hashes
 *
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Encryption
 * @package    Crypt_HMAC
 * @author     Derick Rethans <derick@php.net>
 * @author     Matthew Fonda <mfonda@dotgeek.org>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: HMAC.php,v 1.3 2005/02/20 19:18:29 mfonda Exp $
 * @link       http://pear.php.net/package/Crypt_HMAC
 */

/**
* Calculates RFC 2104 compliant HMACs
*
* @access     public
* @category   Encryption
* @package    Crypt_HMAC
* @author     Derick Rethans <derick@php.net>
* @author     Matthew Fonda <mfonda@dotgeek.org>
* @copyright  1997-2005 The PHP Group
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @link       http://pear.php.net/package/Crypt_HMAC
*/   
class Crypt_HMAC 
{

    /**
    * Hash function to use
    * @var string
    * @access private
    */
    var $_func;

    /**
    * Inner padded key
    * @var string
    * @access private
    */
    var $_ipad;

    /**
    * Outer padded key
    * @var string
    * @access private
    */
    var $_opad;
    
    /**
    * Pack format
    * @var string
    * @access private
    */
    var $_pack;
    
    
    /**
    * Constructor
    * Pass method as first parameter
    *
    * @param string $key  Key to use for hash
    * @param string $func  Hash function used for the calculation
    * @return void
    * @access public
    */
    function Crypt_HMAC($key, $func = 'md5')
    {
        $this->setFunction($func);
        $this->setKey($key);
    }
    
    
    /**
    * Sets hash function
    *
    * @param string $func  Hash function to use
    * @return void
    * @access public
    */
    function setFunction($func)
    {
        if (!$this->_pack = $this->_getPackFormat($func)) {
            die('Unsupported hash function');
        }
        $this->_func = $func;
    }
    
    
    /**
    * Sets key to use with hash
    *
    * @param string $key
    * @return void
    * @access public
    */
    function setKey($key)
    {
        /* 
        * Pad the key as the RFC wishes
        */
        $func = $this->_func;
        
        if (strlen($key) > 64) {
           $key =  pack($this->_pack, $func($key));
        }
        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }
        

        /* Calculate the padded keys and save them */
        $this->_ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $this->_opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
    }
    
    
    /**
    * Gets pack formats for specifed hash function
    *
    * @param string $func
    * @return mixed  false if hash function doesnt exist, pack format on success
    * @access private
    */
    function _getPackFormat($func)
    {
        $packs = array('md5' => 'H32', 'sha1' => 'H40');
        return isset($packs[$func]) ? $packs[$func] : false;
    }
    
    
    /**
    * Hashing function
    *
    * @param  string $data  string that will encrypted
    * @return string
    * @access public
    */
    function hash($data)
    {
        $func = $this->_func;
        return $func($this->_opad . pack($this->_pack, $func($this->_ipad . $data)));
    }

}

