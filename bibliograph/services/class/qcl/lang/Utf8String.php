<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_log_Logger::getInstance()->warn("Utf8String class is deprecated and wil be removed");

/**
 * UTF8-String manipulation class
 * Relies heavily on functions from the source code of PunBB
 * @deptrecated will be removed
 */
class Utf8String
{

  /**
   * String value.
   *
   * @param string
   * @access private
   */
  var $_value = '';


  /**
   * Constructor
   * @param string $str Ut8-encoded string
   */
  function Utf8String( $str = null )
  {
    $this->set( (string) $str );
  }


  /**
   * imports from a non-utf8 string
   * @param string $str
   */
  function import( $str )
  {
    $this->set( utf8_encode($str) );
  }

  /**
   * Returns the character (Unicode code point) at the specified index.
   *
   * @param int Position of the character that will be returned.
   * @return int
   */
  function codePointAt( $index )
  {
    return ord( $this->getCharAt( $index ) );
  }

  /**
   * Gets the string value of an element
   * @param String|string $elem
   */
  function _getStringValue( $elem )
  {
    return is_a( $elem, "String") ? $elem->get() : (string) $elem;
  }

  /**
   * Compares two strings lexicographically.
   *
   * @param String|string $str String (object) to which we will compare.
   * @param bool $caseSensitive If TRUE, comparison is case-sensitive.
   * @return int
   */
  function compareTo( $str, $caseSensitive = true )
  {
    $anotherString = $this->_getStringValue();
    $function = $caseSensitive ? 'strcmp' : 'strcasecmp';
    return $function( $this->_value, $anotherString );
  }

  /**
   * Concatenates the specified string to the end of this string.
   *
   * @param string String that will be concatenated.
   * @return void
   */
  function concat( $str )
  {
    $this->_value .= $str;
  }


  /**
   * Returns the index of the first occurrence of the argument
   * @param string $str
   * return int
   */
  function indexOf($str)
  {
    return strpos($this->_value,$str);
  }

  /**
   * Returns the index of the last occurrence of the argument
   * @param string $str
   * return int
   */
  function lastIndexOf($str)
  {
    return strrpos($this->_value,$str);
  }

  /**
   * Returns true if and only if this string contains the specified sequence
   * of char values.
   *
   * @param string Char Sequence that is being searched.
   * @return bool
   */
  function contains( $charSequence )
  {
    return ereg( $charSequence, $this->_value );
  }

  /**
   * Copies the value of another String object
   * @param String $data
   */
  function copyValueOf( $data )
  {
    if ( is_a($data, 'String') )
    {
      $this->_copyValueOfString( $data );
    }
  }

  /**
   * Private method that copies the value of a String object to this one.
   *
   * @param object String object reference.
   * @return void
   */
  function _copyValueOfString( $data )
  {
    $this->set( $data->get() );
  }

  /**
   * Returns the character that is in position $pos. If this position is out
   * of bounds, it returns FALSE.
   *
   * @param int Position of the character that will be returned.
   * @return char
   */
  function getCharAt( $pos )
  {
    if ( $pos < 0 || $pos > $this->lenght() ) {
      return false;
    } else {
      return $this->_value[$pos];
    }
  }

  /**
   * Alias for lenght method.
   *
   * @see String::lenght()
   */
  function count()
  {
    return $this->lenght();
  }

  function get()
  {
    return $this->_value;
  }

  /**
   * Returns the string lenght.
   *
   * @return int
   */
  function length()
  {
    return strlen( $this->_value );
  }

  /**
   * Verifies if the expression matches the string value. It can be case-or-
   * -not-case-sensitive based on case parameter.
   * If the expression matches, it will return an array containing the
   * matched substrings separated by parentesis, just like ereg or eregi.
   */
  function matches( $expression, $case = false )
  {
    $function = $case ? 'ereg' : 'eregi';
    $function( $expression, $this->_value, $result );
    return $result;
  }

  /**
   * Puts $char at position $pos in the string. Returns TRUE if it has success
   * on it or FALSE if it fails.
   *
   * @return bool
   */
  function putCharAt( $char, $pos )
  {
    if ( $pos >= 0 && $pos < $this->lenght() ) {
      $this->_value[$pos] = $char;
      return true;
    } else {
      return false;
    }
  }

  /**
   * Sets the string value. If there is a maximun size set, it will set the
   * string value to the substring made by the beginning to size position
   *
   * @param string Value that will be passed to the string.
   */
  function set( $value )
  {
    $this->_value = ( (int)$this->_size > 0 )
    ? substr( $value, 0, $this->_size )
    : $value;
  }

  /**
   * Removes white spaces form beginnig and end of string.
   */
  function trim()
  {
    $this->_value = trim( $this->_value );
  }
  /**
   * Changes the string case to uppercase if no position is specified.
   * If a position was specified, changes that position case to upper.
   */
  function toUppercase( $charpos = null )
  {
    if ( $charpos === null || !is_int($charpos) ) {
      $this->_value = strtoupper( $this->_value );
    } else {
      $this->putCharAt( strtoupper($this->getCharAt( $charpos )),
      $charpos );
    }
  }

  /**
   * Changes the string case to lowercase if no position is specified.
   * If a position was specified, changes that position case to lower.
   */
  function toLowercase( $charpos = null )
  {
    if ( $charpos === null || !is_int($charpos) ) {
      $this->_value = strtolower( $this->_value );
    } else {
      $this->putCharAt( strtolower($this->getCharAt( $charpos )),
      $charpos );
    }
  }

  /**
   * Returns a part of the string
   * @param int $first Index of first character
   * @param int[optional, default null] $count Number of characters to fetch, all if null
   */
  function substr ($first,$count=null)
  {
    if ( ! is_null($count) )
    {
      return substr($this->_value,$first,$count);
    }
    return substr($this->_value,$first);
  }

  /**
   * Returns a part of the string
   * @param int $first Index of first character to fetch
   * @param int $last Index of last character to fetch
   */
  function substring($first,$last)
  {
    return $this->substr($first,$last-$first);
  }

  /**
   * Returns the string value of the object
   * @return string
   */
  function toString()
  {
    return $this->_value;
  }

  /**
   * Returns an array of parts of the string split at the given seperator instances
   * @param string $separator A regular expression
   * @return array
   */
   function split($separator)
   {
     $parts = preg_split($separator,$this->_value);
     return $parts;
   }

   /**
    * Returns a string that is the result of a regular
    * expression replace operation
    * @return Utf8String
    */
    function replace($search,$replace)
    {
      return new Utf8String(preg_replace($search,$replace,$this->_value));
    }

    /**
     * Whether the current string is empty
     * @return bool
     */
    function isEmpty()
    {
      return empty($this->_value);
    }

    function toAscii()
    {
      require_once "qcl/lib/punbb_utf8/utils/ascii.php";
      return ( utf8_accents_to_ascii( $this->_value) );
    }

}
