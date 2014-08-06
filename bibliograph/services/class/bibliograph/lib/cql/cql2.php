<?php

/*
 Title: CQL-PHP Version 0.8.1
 Author:  Robert Sanderson
 Date:  2006-02-12
 Copyright: University of Liverpool
 Licence: GPL
 Description:  Port of Python CQLParser to PHP
 Parses CQL Version 1.2
 Usage:  $parser = new CQLParser("query");
 $tree = &parser->query();
 $tree.toCQL();
 $tree.toXCQL();

 Taken from http://www.csc.liv.ac.uk/~azaroth/stuff/
 Adapted by Christian Boulanger for use with
 Bibliograph

  Changes:
   - port to PHP5,
   - added namespae prefix "cql_" (can be removed when using namespaces in PHP5.3)
   - utf-8 support-
   - resolve_prefix()

 */

define('XCQLNamespace',"http://www.loc.gov/zing/cql/xcql/");

/**
 * Class to manage multibyte strings
 * @author Christian Boulanger
 */
class cql_MbString
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

/**
 * The following is derived from Python's ShLex
 * @author Robert Sanderson Original code
 * @author Christian Boulanger UTF-8 support
 *
 */
class cql_SimpleLex
{
  /**
   * The string data being worked with
   * @var cql_MbString
   */
  protected $data;

  /**
   * The length of the data
   * @var int
   */
  protected $datalen= 0;

  /**
   * Whitespace characters
   * @var string
   */
  protected $whitespace = " \t\n";

  /**
   * Operator characters
   * @var string
   */
  protected $operators = "<>=";

  /**
   * Quotation characters
   * @var string
   */
  protected $quotes = '"\'';

  /**
   * The current state
   * @var string
   */
  protected $state = ' ';

  /**
   * The current token
   * @var unknown_type
   */
  protected $token = '';

  /**
   * The next token
   * @var string
   */
  protected $nextToken ="";

  /**
   * The current position
   * @var int
   */
  protected $position = -1;

  /**
   * Debug mode
   * @var int
   */
  protected $debug = 0;

  /**
   * Constructor
   * @param $data
   *    The string data to work with
   * @return void
   */
  public function __construct( $data )
  {
    $this->data = new cql_MbString( $data );
    $this->datalen = $this->data->length();
  }

  /**
   * Returns the current token
   * @return string
   */
  public function get_token()
  {
    /*
     * Read a token from data
     */
    if ($this->position >= $this->datalen)
    {
      return "";
    }

    $cont = 1;

    while ($cont)
    {

      $this->position += 1;
      if ( $this->position >= $this->datalen )
      {
        return trim( $this->token );
      }

      $nextchar = $this->data->charAt( $this->position );

      $is_ws    = strpos($this->whitespace, $nextchar) > -1 ? true : false;
      $is_quote = strpos($this->quotes, $nextchar) > -1 ? true : false;
      $is_oper  = strpos( $this->operators, $nextchar ) > -1 ? true : false;
      $is_word  = ! ($is_ws or $is_quote or $is_oper );

      if ( $this->state == ' ' )
      {
        if ($is_ws)
        {
          if ($this->token != ' ')
          {
            $cont = 0;
          }
          else
          {
            continue;
          }
        }
        elseif ($is_word)
        {
          $this->token = $nextchar;
          $this->state = 'a';
        }
        elseif ($is_quote)
        {
          $this->token = $nextchar;
          $this->state = $nextchar;
        }
        elseif (strpos("<>", $nextchar) > -1)
        {
          $this->token = $nextchar;
          $this->state = '<';
        }
        else
        {
          $this->token = $nextchar;
          $cont = 0;
        }
      }
      elseif ($this->state == "<")
      {
        if ($this->token == ">" && $nextchar == "=")
        {
          $this->token .= $nextchar;
          $this->state = ' ';
        }
        elseif ($this->token == "<" && strpos(">=", $nextchar) > -1)
        {
          $this->token .= $nextchar;
          $this->state = ' ';
        }
        elseif ($nextchar == "/")
        {
          $this->state = " ";
          $this->position -= 1;
        }
        elseif ($is_word)
        {
          $this->state = "a";
          $this->position -= 1;
        }
        elseif ($is_quote)
        {
          $this->state = $nextchar;
          $this->position -= 1;
        }
        else
        {
          $this->state = ' ';
        }
        $cont = 0;
      }
      elseif ( strpos($this->quotes, $this->state) > -1 )
      {
        $this->token .= $nextchar;
        /* allow escape */
        if ($nextchar == $this->state && substr($this->token, -2, 1) != "\\")
        {
          $this->state = ' ';
          $cont = 0;
        }
      }
      elseif ( $this->state == 'a' )
      {
        if ( $is_ws )
        {
          $this->state = ' ';
          if ( strlen($this->token) > 0)
          {
            $cont = 0;
          }
          else
          {
            continue;
          }
        }
        elseif (strpos("<>", $nextchar) > -1)
        {
          $tok = $this->token;
          $this->token = $nextchar;
          $this->state = "<";
          return trim($tok);
        }
        elseif ( $is_word || $is_quote )
        {
          $this->token .= $nextchar;
        }
        else
        {
          /* break */
          $tok = $this->token;
          $this->token = $nextchar;
          $this->position -= 1;
          $this->state = ' ';
          return trim($tok);
        }
      }
    }
    $tok = $this->token;
    $this->token = " ";
    return trim($tok);
  }
}

/**
 * Object which is returned if a problem occurs
 * @author Robert Sanderson
 */
class cql_Diagnostic extends cql_Object
{
  protected $uri;
  protected $message;
  protected $details;

  /**
   * Constructor
   * @param $message
   * @param $type
   * @param $details
   * @return unknown_type
   */
  public function __construct($message, $type=10, $details="")
  {
    $this->message = $message;
    $this->uri = "info:srw/diagnostic/1/$type";
    $this->details = $details;
  }

  public function toTxt()
  {
    return $this->message .
      ( strlen($this->details) > 0 ? ": " .$this->details : "");
  }

  /**
   * Converts the object to an xml representation
   * @return string
   */
  public function toXML() {
    $txt = "<diag:diagnostic xmlns:diag=\"http://www.loc.gov/zing/srw/diagnostic/\">\n";
    $txt .= "  <diag:uri>$this->uri</diag:uri>\n";
    $txt .= "  <diag:message>$this->message</diag:message>\n";
    if ($this->details) {
      $txt .= "  <diag:details>$this->message</diag:details>\n";
    }
    $txt .="</diag:diagnostic>\n";
    return $txt;
  }
}

/**
 * Base class for cql objects
 * @author Robert Sanderson
 *
 */
class cql_Object
{
  public $value;
  public $modifiers;
  public $parentNode;

  protected $config;

  /**
   * Sets the configuration of the object
   * @param $c
   * @return void
   */
  public function set_config( &$c )
  {
    $this->config = $c;
  }

  public function resolve_prefix($pref)
  {
    if ($this->parentNode != null) {
      return $this->parentNode->resolve_prefix($pref);
    } elseif ($this->config != null) {
      return $this->config->resolve_prefix($pref);
    } else {
      /* Not in tree, and no config. Unknown */
      return null;
    }
  }


  public function toCQL() {
    $txt = $this->value;
    if (count($this->modifiers) > 0) {
      foreach ($this->modifiers as $mod) {
        $txt .= "/" . $mod->toCQL();
      }
    }
    return $txt;
  }

  public function toXCQL() {
    return "";
  }

  public function mods_toXCQL($depth=0) {
    $space = str_repeat("  ", $depth);
    $txt = "$space<modifiers>\n";
    foreach ($this->modifiers as $mod) {
      $txt .= $mod->toXCQL($depth+1);
    }
    $txt .= "$space<modifiers>\n";
    return $txt;
  }

  public function toTxt($depth=0) {
    $cl = get_class($this);
    $space = str_repeat("  ", $depth);
    $modtxt = "";
    if ($this->modifiers) {
      foreach ($this->modifiers as $mod) {
        $modtxt .= $mod->toTxt($depth+1);
      }
    }
    return "$space$cl: $this->value\n$modtxt";
  }

}

class cql_Prefixable extends cql_Object
{
  public $prefixes;

  public function add_prefix($p, $uri) {
    $this->prefixes[$p] = $uri;
  }

  public function resolve_prefix($pref)
  {
    if ($this->prefixes && array_key_exists($pref, $this->prefixes))
    {
      return $this->prefixes[$pref];
    }
    elseif ($this->parentNode != null)
    {
      return $this->parentNode->resolve_prefix($pref);
    }
    elseif ($this->config != null)
    {
      return $this->config->resolve_prefix($pref);
    }
    else
    {
      /* Not in tree, and no config. Unknown */
      return null;
    }
  }

  public function prefs_toCQL()
  {
    $txt = "";
    if ($this->prefixes)
    {
      foreach (array_keys($this->prefixes) as $key)
      {
        $val = $this->prefixes[$key];
        if ($key)
        {
          $txt .= ">$key=\"$val\" ";
        }
        else
        {
          $txt .= ">\"$val\" ";
        }
      }
    }
    return $txt;
  }

  public function prefs_toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<prefixes>\n";
    foreach (array_keys($this->prefixes) as $key)
    {
      $val = $this->prefixes[$key];
      $txt .= "$space  <prefix>\n";
      if ($key)
      {
        $txt .= "$space    <name>" . htmlentities($key) . "</name>\n";
      }
      $txt .= "$space    <identifier>" . htmlentities($val) . "</identifier>\n";
      $txt .= "$space  </prefix>\n";
    }
    $txt .= "$space</prefixes>\n";
    return $txt;
  }
}

class cql_Prefixed extends cql_Object
{
  public $prefix;
  public $uri;

  public function split_value()
  {
    $c = substr_count($this->value, '.');
    if ($c > 1)
    {
      /* NASTY! */
      $diag = new cql_Diagnostic("Too many .s in value: $this->value");
    }
    elseif ($this->value{0} == '.')
    {
      throw new LogicException("Null prefix");
    }
    elseif ($c > 0)
    {
      list($pref, $data) = explode('\.', $this->value);
      $this->prefix = $pref;
      $this->value = $data;
    }
  }

  public function resolve_prefix()
  {
    /* resolve my prefix */
    if (!$this->uri && $this->parentNode != null)
    {
      $uri = $this->parentNode->resolve_prefix($this->prefix);
      $this->uri = $uri;
    }
    return $this->uri;
  }

  public function toCQL()
  {
    if ($this->prefix)
    {
      $txt =  "$this->prefix.$this->value";
    }
    else
    {
      $txt =  $this->value;
    }
    if (count($this->modifiers) > 0)
    {
      foreach ($this->modifiers as $mod)
      {
        $txt .= "/" . $mod->toCQL();
      }
    }
    return $txt;
  }

  public function toTxt($depth=0)
  {
    $cl = get_class($this);
    $space = str_repeat("  ", $depth);
    $modtxt = "";
    if ($this->modifiers)
    {
      foreach ($this->modifiers as $mod)
      {
        $modtxt .= $mod->toTxt($depth+1);
      }
    }
    $this->resolve_prefix();
    if ($this->uri)
    {
      return "$space$cl: $this->prefix $this->uri . $this->value\n$modtxt";
    }
    elseif ($this->prefix)
    {
      return "$space$cl: $this->prefix . $this->value\n$modtxt";
    }
    else
    {
      return "$space$cl: $this->value\n$modtxt";
    }
  }
}

class cql_Triple extends cql_Prefixable
{
  public $leftOperand;
  public $rightOperand;
  public $boolean;
  public $sortKeys;

  public function cql_Triple(&$left, &$right, &$bool)
  {
    $this->prefixes = array();
    $this->parentNode = null;
    $this->sortKeys = null;
    $this->leftOperand = $left;
    $this->rightOperand = $right;
    $this->boolean = $bool;
  }

  public function toCQL()
  {
    $prefs = $this->prefs_toCQL();
    return "$prefs(" . $this->leftOperand->toCQL() . " " . $this->boolean->toCQL() . " " . $this->rightOperand->toCQL() . ")";
  }

  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    if ($depth == 0)
    {
      $txt = '<cql_Triple xmlns="' . XCQLNamespace . "\">\n";
    }
    else
    {
      $txt = "$space<cql_Triple>\n";
    }
    if ($this->prefixes)
    {
      $txt .= $this->prefs_toXCQL($depth+1);
    }
    $txt .= $this->boolean->toXCQL($depth+1);
    $txt .= "$space  <leftOperand>\n";
    $txt .= $this->leftOperand->toXCQL($depth+2);
    $txt .= "$space  </leftOperand>\n";
    $txt .= "$space  <rightOperand>\n";
    $txt .= $this->rightOperand->toXCQL($depth+2);
    $txt .= "$space  </rightOperand>\n";

    if ($this->sortKeys)
    {
      $txt .= "$space  <sortKeys>\n";
      foreach ($this->sortKeys as $key)
      {
        $txt .= $key->toXCQL($depth+2);
      }
      $txt .= "$space  </sortKeys>\n";
    }
    $txt .= "$space</cql_Triple>\n";
    return $txt;
  }

  public function toTxt($depth=0)
  {
    $space=str_repeat("  ", $depth);
    $txt = cql_Object::toTxt($depth);
    $txt .= $this->leftOperand->toTxt($depth+1);
    $txt .= $this->boolean->toTxt($depth+1);
    $txt .= $this->rightOperand->toTxt($depth+1);

    if ($this->sortKeys)
    {
      $txt .= "$space  sortBy:\n";
      foreach ($this->sortKeys as $key)
      {
        $txt .= "$space    " . $key->toTxt() . "\n";
      }
    }
    return $txt;
  }
}

class cql_SearchClause extends cql_Prefixable
{
  public $index;
  public $relation;
  public $term;
  public $sortKeys;

  public function __construct(&$i, &$r, &$t)
  {
    $this->parentNode = null;
    $this->sortKeys = null;
    $this->index = $i;
    $this->relation = $r;
    $this->term = $t;
  }

  public function toCQL()
  {
    $prefs = $this->prefs_toCQL();
    return $prefs . $this->index->toCQL() . " " . $this->relation->toCQL() . " \"" . $this->term->toCQL(). "\" ";
  }

  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    if ($depth == 0) {
      $txt = '<cql_SearchClause xmlns="' . XCQLNamespace . "\">\n";
    } else {
      $txt = "$space<cql_SearchClause>\n";
    }
    if ($this->prefixes) {
      $txt .= $this->prefs_toXCQL();
    }

    $txt .= $this->index->toXCQL($depth+1);
    $txt .= $this->relation->toXCQL($depth+1);
    $txt .= $this->term->toXCQL($depth+1);

    if ($this->sortKeys) {
      $txt .= "$space  <sortKeys>\n";
      foreach ($this->sortKeys as $key) {
        $txt .= $key->toXCQL($depth+2);
      }
      $txt .= "$space  </sortKeys>\n";
    }

    $txt .= "$space</cql_SearchClause>\n";
    return $txt;
  }

  public function toTxt($depth=0)
  {
    $space=str_repeat("  ", $depth);
    $txt = cql_Object::toTXT($depth);
    $txt .= $this->index->toTxt($depth+1);
    $txt .= $this->relation->toTxt($depth+1);
    $txt .= $this->term->toTxt($depth+1);
    return $txt;
  }

}

class cql_Index extends cql_Prefixed
{

  public function __construct($data)
  {
    $this->value = $data;
    $this->split_value();
  }

  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    return "$space<cql_Index>" . htmlentities($this->toCQL()) . "</cql_Index>\n";
  }

}

class cql_Relation extends cql_Prefixed
{
  public function __construct($data)
  {
    $this->value = $data;
    $this->split_value();
  }

  public function add_modifiers(&$mods)
  {
    $this->modifiers = $mods;
    foreach ($mods as $m) {
      $m->parentNode = $this;
    }
  }

  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<cql_Relation>\n";
    $txt .= "$space  <value>" . $this->value . "</value>\n";
    if ($this->modifiers) {
      $txt .= $this->mods_toXCQL($depth+1);
    }
    $txt .= "$space</cql_Relation>\n";
    return $txt;
  }

}

/**
 * A CQL Term
 * @author Robert Sanderson
 */
class cql_Term extends cql_Object
{
  public function __construct( $data )
  {
    if ($data{0} == '"' && $data{strlen($data)-1} == '"')
    {
      $data = substr($data, 1, strlen($data)-1);
    }
    $this->value = $data;
  }

  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    return "$space<cql_Term>" . htmlentities($this->value) . "</cql_Term>\n";
  }
}

class cql_Boolean extends cql_Object
{
  public function __construct($data)
  {
    $this->value = $data;
  }

  public function add_modifiers(&$mods)
  {
    $this->modifiers = $mods;
    foreach ($mods as $m) {
      $m->parentNode = $this;
    }
  }

  public function toCQL()
  {
    $txt = strtoupper($this->value);
    if (count($this->modifiers) > 0) {
      foreach ($this->modifiers as $mod) {
        $txt .= "/" . $mod->toCQL();
      }
    }
    return $txt;
  }


  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<cql_Boolean>\n";
    $txt .= "$space  <value>" . $this->value . "</value>\n";
    if ($this->modifiers) {
      $txt .= $this->mods_toXCQL($depth+1);
    }
    $txt .= "$space</cql_Boolean>\n";
    return $txt;
  }

}

class cql_ModifierType extends cql_Prefixed
{

  public function __construct($v)
  {
    $this->value = $v;
    $this->split_value();
  }

  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    return "$space<type>" . htmlentities( $this->toCQL()) . "</type>\n";
  }
}

class cql_ModifierClause extends cql_Object
{

  public $type;
  public $comparison;

  public function __construct($m, $r, $v)
  {
    $this->value = $v;
    $this->comparison = $r;
    $this->type =  new cql_ModifierType( $m );
    $this->type->parentNode = $this;
  }

  public function toCQL()
  {
    return $this->type->toCQL() . $this->comparison . $this->value;
  }

  public function toXCQL($depth=0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<modifier>\n";
    $txt .= $this->type->toXCQL($depth+1);
    if ($this->value) {
      $txt .= "$space  <comparison>" . htmlentities($this->comparison) . "</comparison>\n";
      $txt .= "$space  <value>" . htmlentities($this->value) . "</value>\n";
    }
    $txt .= "$space</modifier>\n";
    return $txt;
  }

  public function toTxt($depth=0)
  {
    $space = str_repeat("  ", $depth);
    $txt = $space . cql_Object::toTxt();
    $t = $this->type->toCQL();
    $txt .= "{$space}  type: $t\n";
    if ($this->value) {
      $txt .= "{$space}  comparison: $this->comparison\n";
      $txt .= "{$space}  value: $this->value\n";
    }
    return $txt;
  }
}


class cql_SortKey extends cql_Object
{
  public $index;

  public function __construct($i)
  {
    $this->index = $i;
  }

  public function add_modifiers(&$mods)
  {
    $this->modifiers = $mods;
    foreach ($mods as $m) {
      $m->parentNode = $this;
    }
  }

  public function toTxt($depth=0)
  {
    return $this->index->toTxt();
  }
}

/**
 * Main parser class
 * @author Robert Sanderson
 *
 */
class cql_Parser
{
  /**
   * Array of terms that are used as boolean operators
   * @var array
   */
  protected $booleans = array("and", "or", "not", "prox");

  /**
   * Array of terms that are used as modifiers (comparison operators)
   * @var array
   */
  protected $modifierList = array("=", ">", ">=", "<", "<=", "<>");

  /**
   * Array of terms that serve as keywords for indicating the sort order
   * @var unknown_type
   */
  protected $sortWords = array("sortby");

  /**
   * The default relation if no one is given. Defaults to "="
   * @var string
   */
  protected $serverChoiceRelation ="=";

  /**
   * The default index name if no one is given.
   * Defaults to "cql.serverChoice"
   */
  protected $serverChoiceIndex = "cql.serverChoice";

  /**
   * The separator character. Defaults to "/"
   * @var unknown_type
   */
  protected $separator = "/";

  /**
   * Configuration data
   * @var unknown_type
   */
  protected $config;

  /**
   * diagnostic object
   */
  protected $diagnostic;

  /**
   * Lexer object
   * @var cql_SimpleLex
   */
  protected $lexer;

  /**
   * The current character
   * @var string
   */
  protected $current ="";

  /**
   * The next character
   * @var string
   */
  protected $next = "";

  /**
   * Constructor
   * @param string $data
   *    The CQL query string
   */
  public function __construct($data)
  {
    $this->lexer = new cql_SimpleLex($data);
    $this->fetch_token();
    $this->fetch_token();
  }

  /**
   * Setter for modifier terms
   * @param array $value
   * @return void
   */
  public function setModifiers( $value )
  {
    if( ! is_array( $value ) )
    {
      throw new InvalidArgumentException("modifiers argument must be array");
    }
    $this->modifierList = $value;
  }

  /**
   * Setter for boolean terms
   * @param array $value
   * @return void
   */
  public function setBooleans( $value )
  {
    if( ! is_array( $value ) )
    {
      throw new InvalidArgumentException("booleans argument must be array");
    }
    $this->booleans = $value;
  }

  /**
   * Setter for sort words
   * @param array $value
   * @return void
   */
  public function setSortWords( $value )
  {
    if( ! is_array( $value ) )
    {
      throw new InvalidArgumentException("sortWord argument must be array");
    }
    $this->sortWords = $value;
  }

  /**
   * Fetch the next token in the cql query string
   * @return unknown_type
   */
  public function fetch_token()
  {
    $this->current = $this->next;
    $this->next = $this->lexer->get_token();
  }

  /**
   * Check whether the given token is one of the boolean terms
   * @param string $token
   * @return bool
   */
  public function is_bool($token)
  {
    return in_array( strtolower($token), $this->booleans);
  }

  /**
   * Check whether the given token is one of the sort words
   * @param $token
   * @return bool
   */
  public function is_sort($token)
  {
    return in_array( strtolower($token), $this->sortWords);
  }

  /**
   * Parse the query
   * @return cql_Object
   */
  public function query()
  {
    $prefs = $this->prefixes();
    $left = $this->subQuery();
    if ($this->diagnostic)
    {
      return $this->diagnostic;
    }

    $cont = 1;
    while ($cont)
    {
      if (!$this->current)
      {
        $cont = 0;
      }
      elseif ($this->is_sort($this->current))
      {
        $left->cql_SortKeys = $this->sortQuery();
      }
      elseif ($this->current == ")")
      {
        return $left;
      }
      else
      {
        $bool = $this->boolean();
        if ($this->diagnostic)
        {
          return $this->diagnostic;
        }
        $right = $this->subQuery();
        if ($this->diagnostic)
        {
          return $this->diagnostic;
        }
        $cql_Triple = new cql_Triple($left, $right, $bool);
        $left->parentNode = $cql_Triple;
        $right->parentNode = $cql_Triple;
        $bool->parentNode = $cql_Triple;
        $left = $cql_Triple;
      }
    }
    foreach (array_keys($prefs) as $key)
    {
      $left->add_prefix($key, $prefs[$key]);
    }
    return $left;
  }

  public function subQuery()
  {
    if ($this->current == "(")
    {
      $this->fetch_token();
      $object = $this->query();
      if ($this->current == ")")
      {
        $this->fetch_token();
      }
      else
      {
        $this->diagnostic = new cql_Diagnostic("Mismatched Parens");
        return null;
      }
    }
    else
    {
      $prefs = $this->prefixes();
      if ($prefs)
      {
        $object = $this->query();
        foreach (array_keys($prefs) as $key)
        {
          $object->add_prefix($key, $prefs[$key]);
        }
      }
      else
      {
        $object = $this->clause();
      }
    }
    return $object;
  }

  public function clause()
  {
    $bool = $this->is_bool($this->next);
    $sort = $this->is_sort($this->next);

    if (!$sort && !$bool && $this->next && !strpos("()", $this->next))
    {
      $index = new cql_Index($this->current);
      $this->fetch_token();
      $rel = $this->relation();
      if (!$this->current)
      {
        $this->diagnostic = new cql_Diagnostic("Missing Term");
        return null;
      }
      else
      {
        $cql_Term = new cql_Term($this->current);
        $this->fetch_token();
      }
    }
    elseif ($this->current &&
      ($bool || $sort || !$this->next || $this->next == ")"))
    {
      $index = new cql_Index($this->serverChoiceIndex);
      $rel = new cql_Relation($this->serverChoiceRelation);
      $cql_Term = new cql_Term($this->current);
      $this->fetch_token();

    }
    elseif ( $this->current == ">" )
    {
      $prefs = $this->prefixes();
      $object = $this->clause();
      foreach (array_keys($prefs) as $key) {
        $object->add_prefix($key, $prefs[$key]);
      }
      return $object;
    }
    else
    {
      $this->diagnostic = new cql_Diagnostic("Expected boolean or relation");
      return null;
    }
    $sc = new cql_SearchClause($index, $rel, $cql_Term);
    $index->parentNode = $sc;
    $rel->parentNode = $sc;
    $cql_Term->parentNode = $sc;
    return $sc;
  }

  public function boolean()
  {
    if ($this->is_bool($this->current))
    {
      $bool = new cql_Boolean($this->current);
      $this->fetch_token();
      $bool->add_modifiers($this->modifiers());
      return $bool;
    } else {
      $this->diagnostic = new cql_Diagnostic("Expected boolean, got $this->current");
      return null;
    }
  }

  public function relation()
  {
    $rel = new cql_Relation($this->current);
    $this->fetch_token();
    $rel->add_modifiers($this->modifiers());
    return $rel;
  }

  public function modifiers()
  {
    $mods = array();
    while ($this->current == $this->separator)
    {
      $this->fetch_token();
      $mod = strtolower($this->current);
      $this->fetch_token();
      if ( in_array( $this->current, $this->modifierList ) )
      {
        $comp = $this->current;
        $this->fetch_token();
        $val = $this->current;
        $this->fetch_token();
      }
      else
      {
        $comp = "";
        $val = "";
      }
      $mods[] = new cql_ModifierClause($mod, $comp, $val);
    }
    return $mods;
  }

  public function prefixes() {
    $prefs = array();
    while ($this->current == ">") {
      $this->fetch_token();
      if ($this->next == "=") {
        $name = $this->current;
        $this->fetch_token();
        $this->fetch_token();
        $identifier = $this->current;
        $this->fetch_token();
      } else {
        $name = "";
        $identifier = $this->current;
        $this->fetch_token();
      }
      if ($identifier{0} == '"' && $identifier{strlen($identifier)-1} == '"') {
        $identifier = substr($identifier, 1, strlen($identifier)-2);
      }
      $prefs[strtolower($name)] = $identifier;
    }
    return $prefs;
  }

  public function sortQuery() {
    $this->fetch_token();
    $keys = array();
    if (!$this->current) {
      $this->diagnostic = new cql_Diagnostic("No sortKeys after sortBy");
      return null;
    } else {
      while ( $this->current ) {
        $index = new cql_Index($this->current);
        $this->fetch_token();
        $mods = $this->modifiers();
        $keys[] = new cql_SortKey($index, $mods);
      }
      return $keys;
    }
  }
}


/**
 * Configuration object
 */
class cql_Config
{
  public $defaultContextSet;
  public $defaultIndex;
  public $defaultRelation;
  public $contextSets;

  public function __construct($zeerex=null)
  {
    $this->contextSets = array();

    if ($zeerex == null) {
      $this->defaultContextSet = "dc";
      $this->defaultIndex = "title";
      $this->defaultRelation = "any";

      $this->contextSets['cql']     = 'info:srw/cql-context-set/1/cql-v1.1';
      $this->contextSets['dc']      = "info:srw/cql-context-set/1/dc-v1.1";
      $this->contextSets['zthes']   = "http://zthes.z3950.org/cql/1.0/";
      $this->contextSets['ccg']     = "http://srw.cheshire3.org/contextSets/ccg/1.1/";
      $this->contextSets['rec']     = "info:srw/cql-context-set/2/rec-1.1";
      $this->contextSets['net']     = "info:srw/cql-context-set/2/net-1.0";
      $this->contextSets['music']   = "info:srw/cql-context-set/3/music-1.0";
      $this->contextSets['rel']     = "info:srw/cql-context-set/2/relevance-1.0";
      $this->contextSets['zeerex']  = "info:srw/cql-context-set/2/zeerex-1.1";
      $this->contextSets['mods']    = "info:srw/cql-context-set/1/mods-1.0";
      $this->contextSets['marc']    = "info:srw/cql-context-set/1/marc-1.0";
    }
  }

  public function add_set($set, $id)
  {
    $this->contextSets[$set] = $id;
  }

  public function resolve_prefix($pref)
  {
    if (array_key_exists($pref, $this->contextSets))
    {
      return $this->contextSets[$pref];
    }
    else
    {
      return null;
    }
  }
}
