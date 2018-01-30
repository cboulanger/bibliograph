<?php

namespace \lib\util;

/**
 * Class to manage multibyte strings
 * @author Christian Boulanger
 */
class MbString
{
  /**
   * The string value
   */
  protected $string;

  /**
   * Constructor
   * @param $string
   * @return unknown_type
   */
  public function __construct( $string )
  {
    $this->string = $string;
  }

  /**
   * Returns the length of the current string
   * @return int
   */
  public function length()
  {
    return mb_strlen ($this->string);
  }

  /**
   * Find position of first occurrence of string string.
   * @param string $needle
   *    The string to find
   * @param int[optional] $offset
   *     May be specified to begin searching an arbitrary number of characters
   *     into the string. Negative values will stop searching at an arbitrary
   *     point prior to the end of the string.
   * @param boolean[optional] $casesensitive
   *     If true (default), perform the search in a case-sensitive way
   * @return int
   *    The numeric position of the first occurrence of needle in the
   *    haystack string. If needle is not found, it returns false.
   */
  public function indexOf ( $needle, $offset=null, $casesensitive=true )
  {
    if ( $casesensitive )
    {
      return mb_strpos( $this->string, $needle, $offset );
    }
    else
    {
      return mb_stripos( $this->string, $needle, $offset );
    }
  }

  /**
   * Find position of last occurrence of a string in a string.
   * @param string $needle
   *    The string to find
   * @param int[optional] $offset
   *     May be specified to begin searching an arbitrary number of characters
   *     into the string. Negative values will stop searching at an arbitrary
   *     point prior to the end of the string.
   * @param boolean[optional] $casesensitive
   *     If true (default), perform the search in a case-sensitive way
   * @return int
   *    The numeric position of the first occurrence of needle in the
   *    haystack string. If needle is not found, it returns false.
   */
  public function lastIndexOf ( $needle, $offset=null, $casesensitive=true )
  {
    if ( $casesensitive )
    {
      return mb_strrpos( $this->string, $needle, $offset );
    }
    else
    {
      return mb_strripos( $this->string, $needle, $offset );
    }
  }

  /**
   * Get part of string.
   * @param int $start
   *    The first position used.
   * @param int[optional] $length
   *    The maximum length of the returned string.
   * @return string
   *    Returns the portion of str specified by the  start and
   *    length parameters.
   */
  public function substr( $start, $length=null )
  {
    return mb_substr ($this->string, $start, $length );
  }

  /**
   * Returns the multibyte character at the given index
   * @param int $index
   * @return string
   */
  public function charAt( $index )
  {
    return $this->substr( $index, 1 );
  }

  /**
   * Count the number of substring occurrences
   * @param string $needle
   *    The string being found.
   * @return int
   *    The number of times the needle substring occurs in the
   *    haystack string.
   */
  public function count( $needle )
  {
    return mb_substr_count ($this->string, $needle);
  }


  /**
   * Returns the encoding of the current string
   * @return string The encoding symbol
   */
  public function getEncoding()
  {
    return mb_detect_encoding ( $this->string );
  }


  /**
   * Regular expression match.
   * @param string $pattern
   *    Must be of the format "/..../" or "/..../i" for case-insensitive
   *    match
   * @param array $matches
   *    Variable that will be populated with the found substrings
   * @return int
   *    Number of matches.
   */
  public function match( $pattern, &$matches )
  {
    if ( substr($pattern,-1) == "i" )
    {
      return mb_eregi ( substr($pattern,1,-2),  $this->string, $matches );
    }
    else
    {
      return mb_ereg ( substr($pattern,1,-1), $this->string, $matches );
    }
  }

  /**
   * Replace regular expression.
   *
   * @param string $pattern
   *    The regular expression pattern. Must be of the format
   *    "/..../p", p being one of the following parameters:
   *    "i" : ignore case will be ignored; "x": ignore whitespace;
   *    match will be executed in multiline mode and line break will
   *    be included in '.'; "p": match will be executed in POSIX mode,
   *    line break will be considered as normal character; "e": replacement
   *    string will be evaluated as PHP expression.
   * @return string
   *    The resultant string on success, or false on error.
   */
  public function replace( $pattern, $replacement )
  {
    if( $pattern[strlen($pattern)-1] !=="/" )
    {
      $option = $pattern[strlen($pattern)-1];
      $pattern = substr($pattern,1,-2);
      return mb_ereg_replace ( $pattern, $replacement, $this->string, $option );
    }
    else
    {
      return mb_ereg_replace ( substr($pattern,1,-1), $replacement, $this->string);
    }
  }

  /**
   * Split multibyte string using regular expression.
   *
   * @param string $pattern
   *    The regular expression pattern, of the format "/..../"
   * @param int[optional] $limit
   *    If optional parameter limit is specified, the string will be split
   *    in $limit elements as maximum.
   * @return array
   *    The result as an array.
   */
  public function split( $pattern, $limit = null)
  {
    return mb_split ( substr($pattern,1-1), $this->string, $limit );
  }

  /**
   * Typecasting the object to a primitive string type
   * @return string
   */
  public function __toString()
  {
    return $this->string;
  }
}