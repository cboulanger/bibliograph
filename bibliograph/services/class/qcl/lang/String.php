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

/**
 * String manipulation class. Assumes that the string is ASCII or some other
 * native character set. For UTF-8 encoded strings, use the Utf8String class.
 *
 * Adapted from http://www.phpclasses.org/browse/package/2396.html
 * @author Original author: Alexander Ramos Jardim (http://www.labma.ufrj.br/~alex/)
 * @author Adapted for qcl library: Christian Boulanger
 * @todo: replace by utf-8 solution
 *
 */
class String
{

  /**
   * If size of the string is set, it will indicate the maximun size of
   * _value.
   *
   * @param int
   * @access private
   */
  var $_size = null;

  /**
   * String value.
   *
   * @param string
   * @access private
   */
  var $_value = '';


  /**
   * Constructor
   */
  function __construct( $str = null, $size = null )
  {
    $this->_size = $size;
    $this->set( (string) $str );
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
   * @return string
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
    $anotherString = $this->_getStringValue($str);
    $function = $caseSensitive ? 'strcmp' : 'strcasecmp';
    return $function( $this->_value, $anotherString );
  }

  /**
   * Concatenates the specified string to the end of this string.
   *
   * @param string String that will be concatenated.
   * @return the modified String object
   */
  function concat( $str )
  {
    $this->_value .= $str;
    return $this;
  }


  /**
   * Returns the index of the first occurrence of the argument
   * @param string $str
   * return int
   * @return int
   */
  function indexOf($str)
  {
    return strpos($this->_value,$str);
  }

  /**
   * Returns the index of the last occurrence of the argument
   * @param string $str
   * return int
   * @return int
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
    return strstr( $this->_value, $charSequence ) ? true : false;
  }

  /**
   * Copies the value of another String object
   * @param String $data
   * @return $this
   */
  function copyValueOf( String $data )
  {
    $this->_copyValueOfString( $data );
    return $this;
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

  /**
   * Returns the string value of the Object
   * @return string
   */
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
   * @param string $expression
   * @param bool $case
   * @return int number of matches or zero if no match
   */
  function matches( $expression, $case = false )
  {
    return preg_match( "/" . $expression . "/" . ($case?"":"i"), $this->_value);
  }

  /**
   * Puts $char at position $pos in the string. Returns TRUE if it has success
   * on it or FALSE if it fails.
   *
   * @param $char
   * @param $pos
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
   * @return String
   */
  function set( $value )
  {
    $this->_value = ( (int)$this->_size > 0 )
    ? substr( $value, 0, $this->_size )
    : $value;
    return $this;
  }

  /**
   * Returns a new string object with white spaces form beginnig and end of string
   * removed
   * @return String
   */
  function trim()
  {
    return new String( trim( $this->_value ) );
  }

  /**
   * Changes the string case to uppercase
   * @return String A new String object with the modified content
   */
  function toUppercase()
  {
    return new String( strtoupper( $this->_value ) );
  }

  /**
   * Changes the string case to lowercase.
   * @param null $charpos
   * @return String A new String object with the modified content
   */
  function toLowercase( $charpos = null )
  {
    return new String( strtoupper( $this->_value ) );
  }

  /**
   * Returns a part of the string
   * @param int $first Index of first character
   * @param int[optional, default null] $count Number of characters to fetch, all if null
   * @return String A new String object with the modified content
   */
  function substr ($first,$count=null)
  {
    if ( ! is_null( $count ) )
    {
      return new String( substr($this->_value,$first,$count) );
    }
    return new String( substr($this->_value,$first) );
  }

  /**
   * Returns a part of the string
   * @param int $first Index of first character to fetch
   * @param int $last Index of last character to fetch
   * @return String A new String object with the modified content
   */
  function substring($first,$last)
  {
    return new String( $this->substr($first,$last-$first) );
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
   * Returns the string value of the object
   * @return string
   */
  function __toString()
  {
    return $this->_value;
  }

  /**
   * Returns an array of parts of the string split at the given seperator instances
   * @param string $separator A regular expression
   * @return array Array of strings
   */
   function split($separator)
   {
     $parts = preg_split($separator,$this->_value);
     return $parts;
   }

  /**
   * Returns a string that is the result of a regular
   * expression replace operation
   * @param $search
   * @param $replace
   * @return String A new String object with the modified content
   */
    function replace($search,$replace)
    {
      return new String( preg_replace($search,$replace,$this->_value) );
    }

    /**
     * Whether the current string is empty
     * @return bool
     */
    function isEmpty()
    {
      return empty($this->_value);
    }
}
