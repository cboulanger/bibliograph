<?php

/*
 Title: CQL-PHP Version 0.8.1
 Author:  Robert Sanderson
 Date:  2006-02-12
 Copyright: University of Liverpool
 Licence: GPL
 Description:  Port of Python CQLParser to PHP
 Parses CQL Version 1.2

  See http://www.loc.gov/standards/sru/cql/

 Usage:  $parser = new CQLParser("query");
 $tree = &parser->query();
 $tree.toCQL();
 $tree.toXCQL();

 Taken from http://www.csc.liv.ac.uk/~azaroth/stuff/
 Adapted by Christian Boulanger for use with
 Bibliograph

  Changes:
   - port to PHP5/7
   - utf-8 support
   - resolve_prefix()
   - Renamed Object to CqlObject for PHP7.2 compatibility

 */

namespace lib\cql;

use lib\util\MbString;
use \LogicException;

define('XCQLNamespace', "http://www.loc.gov/zing/cql/xcql/");

/**
 * Main parser class
 * @author Robert Sanderson
 *
 */
class Parser
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
   * @var array
   */
  protected $sortWords = array("sortby");

  /**
   * The default relation if no one is given. Defaults to "="
   * @var string
   */
  protected $serverChoiceRelation = "=";

  /**
   * The default index name if no one is given.
   * Defaults to "cql.serverChoice"
   */
  protected $serverChoiceIndex = "cql.serverChoice";

  /**
   * The separator character. Defaults to "/"
   * @var string
   */
  protected $separator = "/";

  /**
   * Configuration data
   * @var array
   */
  protected $config;

  /**
   * diagnostic object
   * @var Diagnostic
   */
  protected $diagnostic;

  /**
   * Lexer object
   * @var SimpleLex
   */
  protected $lexer;

  /**
   * The current character
   * @var string
   */
  protected $current = "";

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
    $this->lexer = new SimpleLex($data);
    $this->fetch_token();
    $this->fetch_token();
  }

  /**
   * Setter for modifier terms
   * @param ModifierClause[] $value
   * @return void
   */
  public function setModifiers(array $value)
  {
    $this->modifierList = $value;
  }

  /**
   * Setter for boolean terms
   * @param Boolean[] $value
   * @return void
   */
  public function setBooleans(array $value)
  {
    $this->booleans = $value;
  }

  /**
   * Setter for sort words
   * @param SortKey[] $value
   * @return void
   */
  public function setSortWords(array $value)
  {
    $this->sortWords = $value;
  }

  /**
   * Fetch the next token in the cql query string
   * @return void
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
    return in_array(strtolower($token), $this->booleans);
  }

  /**
   * Check whether the given token is one of the sort words
   * @param $token
   * @return bool
   */
  public function is_sort($token)
  {
    return in_array(strtolower($token), $this->sortWords);
  }

  /**
   * Parse the query
   * @return Diagnostic|SearchClause|Triple|null
   */
  public function query()
  {
    $prefs = $this->prefixes();
    $left = $this->subQuery();
    if ($this->diagnostic) {
      return $this->diagnostic;
    }

    $cont = 1;
    while ($cont) {
      if (!$this->current) {
        $cont = 0;
      } elseif ($this->is_sort($this->current)) {
        $left->SortKeys = $this->sortQuery();
      } elseif ($this->current == ")") {
        return $left;
      } else {
        $bool = $this->boolean();
        if ($this->diagnostic) {
          return $this->diagnostic;
        }
        $right = $this->subQuery();
        if ($this->diagnostic) {
          return $this->diagnostic;
        }
        $Triple = new Triple($left, $right, $bool);
        $left->parentNode = $Triple;
        $right->parentNode = $Triple;
        $bool->parentNode = $Triple;
        $left = $Triple;
      }
    }
    foreach (array_keys($prefs) as $key) {
      $left->add_prefix($key, $prefs[$key]);
    }
    return $left;
  }

  /**
   * @return Diagnostic|SearchClause|Triple|null
   */
  public function subQuery()
  {
    if ($this->current == "(") {
      $this->fetch_token();
      $object = $this->query();
      if ($this->current == ")") {
        $this->fetch_token();
      } else {
        $this->diagnostic = new Diagnostic("Mismatched Parens");
        return null;
      }
    } else {
      $prefs = $this->prefixes();
      if ($prefs) {
        $object = $this->query();
        foreach (array_keys($prefs) as $key) {
          $object->add_prefix($key, $prefs[$key]);
        }
      } else {
        $object = $this->clause();
      }
    }
    return $object;
  }

  public function clause()
  {
    $bool = $this->is_bool($this->next);
    $sort = $this->is_sort($this->next);

    if (!$sort && !$bool && $this->next && !strpos("()", $this->next)) {
      $index = new Index($this->current);
      $this->fetch_token();
      $rel = $this->relation();
      if (!$this->current) {
        $this->diagnostic = new Diagnostic("Missing Term");
        return null;
      } else {
        $Term = new Term($this->current);
        $this->fetch_token();
      }
    } elseif ($this->current &&
      ($bool || $sort || !$this->next || $this->next == ")")) {
      $index = new Index($this->serverChoiceIndex);
      $rel = new Relation($this->serverChoiceRelation);
      $Term = new Term($this->current);
      $this->fetch_token();

    } elseif ($this->current == ">") {
      $prefs = $this->prefixes();
      $object = $this->clause();
      foreach (array_keys($prefs) as $key) {
        $object->add_prefix($key, $prefs[$key]);
      }
      return $object;
    } else {
      $this->diagnostic = new Diagnostic("Expected boolean or relation");
      return null;
    }
    $sc = new SearchClause($index, $rel, $Term);
    $index->parentNode = $sc;
    $rel->parentNode = $sc;
    $Term->parentNode = $sc;
    return $sc;
  }

  public function boolean()
  {
    if ($this->is_bool($this->current)) {
      $bool = new Boolean($this->current);
      $this->fetch_token();
      $mods = $this->modifiers();
      $bool->add_modifiers($mods);
      return $bool;
    } else {
      $this->diagnostic = new Diagnostic("Expected boolean, got $this->current");
      return null;
    }
  }

  /**
   * @return Relation
   */
  public function relation()
  {
    $rel = new Relation($this->current);
    $this->fetch_token();
    $mods = $this->modifiers();
    $rel->add_modifiers($mods);
    return $rel;
  }

  /**
   * @return ModifierClause[]
   */
  public function modifiers()
  {
    $mods = array();
    while ($this->current == $this->separator) {
      $this->fetch_token();
      $mod = strtolower($this->current);
      $this->fetch_token();
      if (in_array($this->current, $this->modifierList)) {
        $comp = $this->current;
        $this->fetch_token();
        $val = $this->current;
        $this->fetch_token();
      } else {
        $comp = "";
        $val = "";
      }
      $mods[] = new ModifierClause($mod, $comp, $val);
    }
    return $mods;
  }

  public function prefixes()
  {
    $prefs = [];
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
      if ($identifier{0} == '"' && $identifier{strlen($identifier) - 1} == '"') {
        $identifier = substr($identifier, 1, strlen($identifier) - 2);
      }
      $prefs[strtolower($name)] = $identifier;
    }
    return $prefs;
  }

  /**
   * @return SortKey[]|null
   */
  public function sortQuery()
  {
    $this->fetch_token();
    $keys = array();
    if (!$this->current) {
      $this->diagnostic = new Diagnostic("No sortKeys after sortBy");
      return null;
    } else {
      while ($this->current) {
        $index = new Index($this->current);
        $this->fetch_token();
        $mods = $this->modifiers();
        $keys[] = new SortKey($index, $mods);
      }
      return $keys;
    }
  }
}


/**
 * Configuration object
 */
class Config
{
  /**
   * @var string
   */
  public $defaultContextSet;
  /**
   * @var string
   */
  public $defaultIndex;
  /**
   * @var string
   */
  public $defaultRelation;
  /**
   * @var array
   */
  public $contextSets;

  public function __construct($zeerex = null)
  {
    $this->contextSets = [];

    if ($zeerex == null) {
      $this->defaultContextSet = "dc";
      $this->defaultIndex = "title";
      $this->defaultRelation = "any";

      $this->contextSets['cql'] = 'info:srw/cql-context-set/1/cql-v1.1';
      $this->contextSets['dc'] = "info:srw/cql-context-set/1/dc-v1.1";
      $this->contextSets['zthes'] = "http://zthes.z3950.org/cql/1.0/";
      $this->contextSets['ccg'] = "http://srw.cheshire3.org/contextSets/ccg/1.1/";
      $this->contextSets['rec'] = "info:srw/cql-context-set/2/rec-1.1";
      $this->contextSets['net'] = "info:srw/cql-context-set/2/net-1.0";
      $this->contextSets['music'] = "info:srw/cql-context-set/3/music-1.0";
      $this->contextSets['rel'] = "info:srw/cql-context-set/2/relevance-1.0";
      $this->contextSets['zeerex'] = "info:srw/cql-context-set/2/zeerex-1.1";
      $this->contextSets['mods'] = "info:srw/cql-context-set/1/mods-1.0";
      $this->contextSets['marc'] = "info:srw/cql-context-set/1/marc-1.0";
    }
  }

  public function add_set($set, $id)
  {
    $this->contextSets[$set] = $id;
  }

  /**
   * @param string $pref Prefix
   * @return string|null
   */
  public function resolve_prefix($pref)
  {
    if (array_key_exists($pref, $this->contextSets)) {
      return $this->contextSets[$pref];
    } else {
      return null;
    }
  }
}


/**
 * The following is derived from Python's ShLex
 * @author Robert Sanderson Original code
 * @author Christian Boulanger UTF-8 support
 *
 */
class SimpleLex
{
  /**
   * The string data being worked with
   * @var MbString
   */
  protected $data;

  /**
   * The length of the data
   * @var int
   */
  protected $datalen = 0;

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
   * @var string
   */
  protected $token = '';

  /**
   * The next token
   * @var string
   */
  protected $nextToken = "";

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
  public function __construct($data)
  {
    $this->data = new MbString($data);
    $this->datalen = $this->data->length();
  }

  /**
   * Returns the current token
   * @return string
   */
  public function get_token()
  {
    if ($this->position >= $this->datalen) {
      return "";
    }

    $cont = 1;

    while ($cont) {

      $this->position += 1;
      if ($this->position >= $this->datalen) {
        return trim($this->token);
      }

      $nextchar = $this->data->charAt($this->position);

      $is_ws = strpos($this->whitespace, $nextchar) > -1 ? true : false;
      $is_quote = strpos($this->quotes, $nextchar) > -1 ? true : false;
      $is_oper = strpos($this->operators, $nextchar) > -1 ? true : false;
      $is_word = !($is_ws or $is_quote or $is_oper);

      if ($this->state == ' ') {
        if ($is_ws) {
          if ($this->token != ' ') {
            $cont = 0;
          } else {
            continue;
          }
        } elseif ($is_word) {
          $this->token = $nextchar;
          $this->state = 'a';
        } elseif ($is_quote) {
          $this->token = $nextchar;
          $this->state = $nextchar;
        } elseif (strpos("<>", $nextchar) > -1) {
          $this->token = $nextchar;
          $this->state = '<';
        } else {
          $this->token = $nextchar;
          $cont = 0;
        }
      } elseif ($this->state == "<") {
        if ($this->token == ">" && $nextchar == "=") {
          $this->token .= $nextchar;
          $this->state = ' ';
        } elseif ($this->token == "<" && strpos(">=", $nextchar) > -1) {
          $this->token .= $nextchar;
          $this->state = ' ';
        } elseif ($nextchar == "/") {
          $this->state = " ";
          $this->position -= 1;
        } elseif ($is_word) {
          $this->state = "a";
          $this->position -= 1;
        } elseif ($is_quote) {
          $this->state = $nextchar;
          $this->position -= 1;
        } else {
          $this->state = ' ';
        }
        $cont = 0;
      } elseif (strpos($this->quotes, $this->state) > -1) {
        $this->token .= $nextchar;
        /* allow escape */
        if ($nextchar == $this->state && substr($this->token, -2, 1) != "\\") {
          $this->state = ' ';
          $cont = 0;
        }
      } elseif ($this->state == 'a') {
        if ($is_ws) {
          $this->state = ' ';
          if (strlen($this->token) > 0) {
            $cont = 0;
          } else {
            continue;
          }
        } elseif (strpos("<>", $nextchar) > -1) {
          $tok = $this->token;
          $this->token = $nextchar;
          $this->state = "<";
          return trim($tok);
        } elseif ($is_word || $is_quote) {
          $this->token .= $nextchar;
        } else {
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
class Diagnostic extends CqlObject
{
  /**
   * @var string
   */
  protected $uri;
  /**
   * @var string
   */
  protected $message;
  /**
   * @var string
   */
  protected $details;

  /**
   * Constructor
   * @param string $message
   * @param int $type
   * @param string $details
   * @return void
   */
  public function __construct($message, $type = 10, $details = "")
  {
    $this->message = $message;
    $this->uri = "info:srw/diagnostic/1/$type";
    $this->details = $details;
  }

  public function toTxt($depth = 0)
  {
    return $this->message .
      (strlen($this->details) > 0 ? ": " . $this->details : "");
  }

  /**
   * Converts the object to an xml representation
   * @return string
   */
  public function toXML()
  {
    $txt = "<diag:diagnostic xmlns:diag=\"http://www.loc.gov/zing/srw/diagnostic/\">\n";
    $txt .= "  <diag:uri>$this->uri</diag:uri>\n";
    $txt .= "  <diag:message>$this->message</diag:message>\n";
    if ($this->details) {
      $txt .= "  <diag:details>$this->message</diag:details>\n";
    }
    $txt .= "</diag:diagnostic>\n";
    return $txt;
  }
}

/**
 * Base class for cql objects
 * @author Robert Sanderson
 *
 */
class CqlObject
{
  /** @var string */
  public $value;
  /** @var ModifierClause[] */
  public $modifiers;
  /** @var \lib\cql\CqlObject */
  public $parentNode;
  /** @var Config */
  protected $config;

  /**
   * Sets the configuration of the object
   * @param Config $c
   * @return void
   */
  public function set_config(Config &$c)
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


  public function toCQL()
  {
    $txt = $this->value;
    if (is_array($this->modifiers) && count($this->modifiers) > 0) {
      foreach ($this->modifiers as $mod) {
        $txt .= "/" . $mod->toCQL();
      }
    }
    return $txt;
  }

  public function toXCQL($depth=0)
  {
    return "";
  }

  public function mods_toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<modifiers>\n";
    foreach ($this->modifiers as $mod) {
      $txt .= $mod->toXCQL($depth + 1);
    }
    $txt .= "$space<modifiers>\n";
    return $txt;
  }

  public function toTxt($depth = 0)
  {
    $cl = get_class($this);
    $space = str_repeat("  ", $depth);
    $modtxt = "";
    if ($this->modifiers) {
      foreach ($this->modifiers as $mod) {
        $modtxt .= $mod->toTxt($depth + 1);
      }
    }
    return "$space$cl: $this->value\n$modtxt";
  }

}

class Prefixable extends CqlObject
{
  /** @var array */
  public $prefixes;

  /**
   * @param string $p
   * @param string $uri
   */
  public function add_prefix($p, $uri)
  {
    $this->prefixes[$p] = $uri;
  }

  public function resolve_prefix($pref)
  {
    if ($this->prefixes && array_key_exists($pref, $this->prefixes)) {
      return $this->prefixes[$pref];
    } elseif ($this->parentNode != null) {
      return $this->parentNode->resolve_prefix($pref);
    } elseif ($this->config != null) {
      return $this->config->resolve_prefix($pref);
    } else {
      /* Not in tree, and no config. Unknown */
      return null;
    }
  }

  public function prefs_toCQL()
  {
    $txt = "";
    if ($this->prefixes) {
      foreach (array_keys($this->prefixes) as $key) {
        $val = $this->prefixes[$key];
        if ($key) {
          $txt .= ">$key=\"$val\" ";
        } else {
          $txt .= ">\"$val\" ";
        }
      }
    }
    return $txt;
  }

  public function prefs_toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<prefixes>\n";
    foreach (array_keys($this->prefixes) as $key) {
      $val = $this->prefixes[$key];
      $txt .= "$space  <prefix>\n";
      if ($key) {
        $txt .= "$space    <name>" . htmlentities($key) . "</name>\n";
      }
      $txt .= "$space    <identifier>" . htmlentities($val) . "</identifier>\n";
      $txt .= "$space  </prefix>\n";
    }
    $txt .= "$space</prefixes>\n";
    return $txt;
  }
}

class Prefixed extends CqlObject
{
  public $prefix;
  public $uri;

  public function split_value()
  {
    $c = substr_count($this->value, '.');
    if ($c > 1) {
      /* NASTY! */
      $diag = new Diagnostic("Too many .s in value: $this->value");
    } elseif ($this->value{0} == '.') {
      throw new LogicException("Null prefix");
    } elseif ($c > 0) {
      list($pref, $data) = explode('.', $this->value);
      $this->prefix = $pref;
      $this->value = $data;
    }
  }

  public function resolve_prefix($pref)
  {
    /* resolve my prefix */
    if (!$this->uri && $this->parentNode != null) {
      $uri = $this->parentNode->resolve_prefix($this->prefix);
      $this->uri = $uri;
    }
    return $this->uri;
  }

  public function toCQL()
  {
    if ($this->prefix) {
      $txt = "$this->prefix.$this->value";
    } else {
      $txt = $this->value;
    }
    if (is_array($this->modifiers) && count($this->modifiers) > 0) {
      foreach ($this->modifiers as $mod) {
        $txt .= "/" . $mod->toCQL();
      }
    }
    return $txt;
  }

  public function toTxt($depth = 0)
  {
    $cl = get_class($this);
    $space = str_repeat("  ", $depth);
    $modtxt = "";
    if ($this->modifiers) {
      foreach ($this->modifiers as $mod) {
        $modtxt .= $mod->toTxt($depth + 1);
      }
    }
    $this->resolve_prefix("");
    if ($this->uri) {
      return "$space$cl: $this->prefix $this->uri . $this->value\n$modtxt";
    } elseif ($this->prefix) {
      return "$space$cl: $this->prefix . $this->value\n$modtxt";
    } else {
      return "$space$cl: $this->value\n$modtxt";
    }
  }
}

class Triple extends Prefixable
{
  /** @var \lib\cql\CqlObject */
  public $leftOperand;
  /** @var \lib\cql\CqlObject */
  public $rightOperand;
  /** @var \lib\cql\Boolean */
  public $boolean;
  /** @var SortKey[] */
  public $sortKeys;

  public function __construct(\lib\cql\CqlObject &$left, \lib\cql\CqlObject &$right, \lib\cql\Boolean &$boolean)
  {
    $this->prefixes = [];
    $this->parentNode = null;
    $this->sortKeys = null;
    $this->leftOperand = $left;
    $this->rightOperand = $right;
    $this->boolean = $boolean;
  }

  public function toCQL()
  {
    $prefs = $this->prefs_toCQL();
    return "$prefs(" . $this->leftOperand->toCQL() . " " . $this->boolean->toCQL() . " " . $this->rightOperand->toCQL() . ")";
  }

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    if ($depth == 0) {
      $txt = '<Triple xmlns="' . XCQLNamespace . "\">\n";
    } else {
      $txt = "$space<Triple>\n";
    }
    if ($this->prefixes) {
      $txt .= $this->prefs_toXCQL($depth + 1);
    }
    $txt .= $this->boolean->toXCQL($depth + 1);
    $txt .= "$space  <leftOperand>\n";
    $txt .= $this->leftOperand->toXCQL($depth + 2);
    $txt .= "$space  </leftOperand>\n";
    $txt .= "$space  <rightOperand>\n";
    $txt .= $this->rightOperand->toXCQL($depth + 2);
    $txt .= "$space  </rightOperand>\n";

    if ($this->sortKeys) {
      $txt .= "$space  <sortKeys>\n";
      foreach ($this->sortKeys as $key) {
        $txt .= $key->toXCQL($depth + 2);
      }
      $txt .= "$space  </sortKeys>\n";
    }
    $txt .= "$space</Triple>\n";
    return $txt;
  }

  public function toTxt($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = CqlObject::toTxt($depth);
    $txt .= $this->leftOperand->toTxt($depth + 1);
    $txt .= $this->boolean->toTxt($depth + 1);
    $txt .= $this->rightOperand->toTxt($depth + 1);

    if ($this->sortKeys) {
      $txt .= "$space  sortBy:\n";
      foreach ($this->sortKeys as $key) {
        $txt .= "$space    " . $key->toTxt() . "\n";
      }
    }
    return $txt;
  }
}

class SearchClause extends Prefixable
{
  /** @var Index */
  public $index;
  /** @var Relation */
  public $relation;
  /** @var Term */
  public $term;
  /** @var SortKey[] */
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
    return $prefs . $this->index->toCQL() . " " . $this->relation->toCQL() . " \"" . $this->term->toCQL() . "\" ";
  }

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    if ($depth == 0) {
      $txt = '<SearchClause xmlns="' . XCQLNamespace . "\">\n";
    } else {
      $txt = "$space<SearchClause>\n";
    }
    if ($this->prefixes) {
      $txt .= $this->prefs_toXCQL();
    }

    $txt .= $this->index->toXCQL($depth + 1);
    $txt .= $this->relation->toXCQL($depth + 1);
    $txt .= $this->term->toXCQL($depth + 1);

    if ($this->sortKeys) {
      $txt .= "$space  <sortKeys>\n";
      foreach ($this->sortKeys as $key) {
        $txt .= $key->toXCQL($depth + 2);
      }
      $txt .= "$space  </sortKeys>\n";
    }

    $txt .= "$space</SearchClause>\n";
    return $txt;
  }

  public function toTxt($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = CqlObject::toTXT($depth);
    $txt .= $this->index->toTxt($depth + 1);
    $txt .= $this->relation->toTxt($depth + 1);
    $txt .= $this->term->toTxt($depth + 1);
    return $space . $txt;
  }

}

class Index extends Prefixed
{

  public function __construct($data)
  {
    $this->value = $data;
    $this->split_value();
  }

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    return "$space<Index>" . htmlentities($this->toCQL()) . "</Index>\n";
  }

}

class Relation extends Prefixed
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

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<Relation>\n";
    $txt .= "$space  <value>" . $this->value . "</value>\n";
    if ($this->modifiers) {
      $txt .= $this->mods_toXCQL($depth + 1);
    }
    $txt .= "$space</Relation>\n";
    return $txt;
  }

}

/**
 * A CQL Term
 * @author Robert Sanderson
 */
class Term extends CqlObject
{
  public function __construct($data)
  {
    if ($data{0} == '"' && $data{strlen($data) - 1} == '"') {
      $data = substr($data, 1, strlen($data) - 1);
    }
    $this->value = $data;
  }

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    return "$space<Term>" . htmlentities($this->value) . "</Term>\n";
  }
}

class Boolean extends CqlObject
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
    if (is_array($this->modifiers) && count($this->modifiers) > 0) {
      foreach ($this->modifiers as $mod) {
        $txt .= "/" . $mod->toCQL();
      }
    }
    return $txt;
  }

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<Boolean>\n";
    $txt .= "$space  <value>" . $this->value . "</value>\n";
    if ($this->modifiers) {
      $txt .= $this->mods_toXCQL($depth + 1);
    }
    $txt .= "$space</Boolean>\n";
    return $txt;
  }

}

class ModifierType extends Prefixed
{

  public function __construct($v)
  {
    $this->value = $v;
    $this->split_value();
  }

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    return "$space<type>" . htmlentities($this->toCQL()) . "</type>\n";
  }
}

class ModifierClause extends CqlObject
{
  public $type;
  public $comparison;

  public function __construct($m, $r, $v)
  {
    $this->value = $v;
    $this->comparison = $r;
    $this->type = new ModifierType($m);
    $this->type->parentNode = $this;
  }

  public function toCQL()
  {
    return $this->type->toCQL() . $this->comparison . $this->value;
  }

  public function toXCQL($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = "$space<modifier>\n";
    $txt .= $this->type->toXCQL($depth + 1);
    if ($this->value) {
      $txt .= "$space  <comparison>" . htmlentities($this->comparison) . "</comparison>\n";
      $txt .= "$space  <value>" . htmlentities($this->value) . "</value>\n";
    }
    $txt .= "$space</modifier>\n";
    return $txt;
  }

  public function toTxt($depth = 0)
  {
    $space = str_repeat("  ", $depth);
    $txt = $space . CqlObject::toTxt();
    $t = $this->type->toCQL();
    $txt .= "{$space}  type: $t\n";
    if ($this->value) {
      $txt .= "{$space}  comparison: $this->comparison\n";
      $txt .= "{$space}  value: $this->value\n";
    }
    return $txt;
  }
}


class SortKey extends CqlObject
{
  /** @var Index */
  public $index;

  public function __construct(Index $i, array &$mods)
  {
    $this->index = $i;
    if ($mods) $this->add_modifiers($mods);
  }

  public function add_modifiers(array &$mods)
  {
    $this->modifiers = $mods;
    foreach ($mods as $m) {
      $m->parentNode = $this;
    }
  }

  public function toTxt($depth = 0)
  {
    return $this->index->toTxt();
  }
}

