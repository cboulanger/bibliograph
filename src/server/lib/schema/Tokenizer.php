<?php

/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

namespace lib\schema;

/**
 * Class Tokenizer
 *
 * @package bibliograph\schema
 */
class Tokenizer
{
  /**
   * @var array
   */
  private $string;

  function __construct( $utf8_string )
  {
    $this->string = preg_split("//u", $utf8_string, -1, PREG_SPLIT_NO_EMPTY);
  }


  /**
   * tokenize and returns the tokens in the string as an array
   * expressions in quotation marks are preserved as a token
   * @return array
   */
  public function tokenize()
  {
    $tokens   = array();
    $token    = "";
    $isQuoted = false;
    $isWord   = false;
    do
    {
      $char = array_shift($this->string);

      // whitespace
      if( trim($char) == ""  )
      {
        if ( strlen($token) > 0 )
        {
          // if in quotes, use verbatim
          if ( $isQuoted )
          {
            $token .= $char;
          }
          // word boundary outside quotations -> skip whitespace and begin new token
          else
          {
            $tokens[] = $token;
            $token = "";
          }
        }
        continue;
      }

      // quotation mark: toggle flag
      if( $char == '"' )
      {
        if( ! $isQuoted )
        {
          if( strlen($token) > 0)
          {
            $tokens[] = $token;
          }
          $token = $char;
          $isQuoted = true;
        }
        else
        {
          $tokens[] = $token . $char;
          $token = "";
          $isQuoted = false;
        }
        continue;
      }

      // unicode alphanumeric character -> add to token
      if( preg_match("/[\pL\pN]/u", $char ) )
      {
        // if we're outside of a word, start new token
        if ( ! $isWord and ! $isQuoted and strlen($token) > 0)
        {
          $tokens[] = $token;
          $token = "";
        }
        $token .= $char;
        $isWord = true;
        continue;
      }

      // not an alphanumeric character:

      // if we have a word outside a quotation, begin a new token
      if( $isWord and ! $isQuoted and strlen($token) > 0 )
      {
        if( $char == "/" ) { $token .= $char; continue; }
        $tokens[] = $token;
        $token = "";
      }

      // add to current token
      $isWord = false;
      $token .= $char;
    }
    while(count($this->string));

    // add remaining token
    if( strlen($token) > 0 ) $tokens[] = $token;

    return $tokens;
  }
}