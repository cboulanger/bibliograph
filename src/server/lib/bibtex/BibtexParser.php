<?php
/* ************************************************************************

  structwsf

  A platform-independent Web services framework for accessing
  and exposing structured RDF data.

  http://code.google.com/p/structwsf/

  Copyright:
    Frederick Giasson, Structured Dynamics LLC.

  License:
    Apache License 2.0
    See the LICENSE file in this directory for details.

  Authors:
    * Frederick Giasson, Structured Dynamics LLC. (Original author)
    * Chritian Boulanger (Modified for bibliograph)

************************************************************************ */

namespace lib\bibtex;

/**
 * Bibtex parsing class
 * @author Frederick Giasson, Structured Dynamics LLC.
 */
class BibtexParser
{
  public $items = array();
  private $fileContent = "";
  private $cursor = 0;  // The parser's file cursor

  /**
   * Parses the given bibtex string
   * @param string $content Bibtex string
   * @return BibtexItem[]
   */
  function parse($content) : array
  {
    $this->fileContent = $content;

    // Lets normalize the content of the file
    $this->fileContent = str_replace(array("\t", "\r", "\n"), "", $this->fileContent);

    // Remove additional spaces.
    $this->fileContent = preg_replace("#\s\s+#", " ", $this->fileContent);

    // Fix potential bibtex format issues
    $this->fixFormatIssues();

    return $this->_parse();
  }

  /**
   * Implementation of parse()
   * @return BibtexItem[]
   */
  private function _parse() : array
  {
    // Iterates for all bibtex items.

    while ($this->nextItem() !== FALSE) {
      // Create a new bib item
      $item = new BibtexItem();
      $item->addType($this->getItemType());
      $item->addID($this->getItemID());

      while (($property = $this->getItemProperty()) !== FALSE) {
        if (trim($property[1])) {
          $item->addProperty($property[0], $property[1]);
        }
      }

      array_push($this->items, $item);
    }
    return $this->items;

  }

  /**
   * Move the cursor to the next bib item
   * @return int|false
   */
  private function nextItem()
  {
    $this->cursor = strpos($this->fileContent, "@", $this->cursor);

    if ($this->cursor !== FALSE) {
      $this->cursor++;
      return $this->cursor;
    } else {
      return FALSE;
    }
  }

  /**
   * Get the type of the item at cursor's position
   * @return string
   */
  private function getItemType()
  {
    $end = strpos($this->fileContent, "{", $this->cursor);

    $type = strtolower(substr($this->fileContent, $this->cursor, ($end - $this->cursor)));

    // Move the cursor
    $this->cursor = $end + 1;

    // Lets remove all spaces and tabs
    return (str_replace(" ", "", $type));
  }

  /**
   * Get the ID of the item at cursor's position
   * @return string
   */
  private function getItemID()
  {
    $end = strpos($this->fileContent, ",", $this->cursor);

    $id = substr($this->fileContent, $this->cursor, ($end - $this->cursor));

    // Move the cursor
    $this->cursor = $end + 1;

    // Lets remove all spaces and tabs
    return ($id);
  }

  /**
   * Get the next Property of the item at cursor's position
   * @return array|false
   */
  private function getItemProperty()
  {
    if ($this->cursor >= strlen($this->fileContent) ){
      return false;
    }
    // First, check if we reached the end of the bib item.
    if ($this->fileContent[$this->cursor] == "@") {
      return (FALSE);
    }

    if ($this->fileContent[$this->cursor] == "}") {
      // Move the cursor
      $this->cursor += 1;
      return (FALSE);
    }

    // Then lets check if we are facing an integer value:
    $pattern = '/(.*)?[\s]*=(.*),/U';
    $match = preg_match($pattern, $this->fileContent, $matches, NULL, $this->cursor);

    if ($match) {
      if (strpos($matches[0], '"') === FALSE && strpos($matches[0], '{') === FALSE) {
        // Move the cursor
        $this->cursor += strlen($matches[0]);

        return ([strtolower(str_replace(" ", "", $matches[1])), str_replace(" ", "", $matches[2])]);
      } else {
        // End patterns:
        // (1) "}   =>   ["]{1}[\s]*[\}]{1}
        // (2) },}    =>   [\}]{1}[\s]*[,]{1}[\s]*[\}]{1}
        // (3) }}   =>   [\}]{1}[\s]*[\s]*[\}]{1}

        // (["]{1}[\s]*[\}]{1}|[\}]{1}[\s]*[,]{1}[\s]*[\}]{1}){1}

        // Next items patterns:
        // (1) ",     =>   ["]{1}[\s]*[,]{1}
        // (2) },   =>   [\}]{1}[\s]*[,]{1}
        // (3) "}   =>   ["]{1}[\s]*[\}]{1}

        // (["]{1}[\s]*[,]{1}|[\}]{1}[\s]*[,]{1}|["]{1}[\s]*[\}]{1}){1}

        // Then  extract the property->value for that bib item
        $pattern = '/(.*)[\s]*=[\s]*["\{]{1}(.*)(["]{1}[\s]*[,]{1}|[\}]{1}[\s]*[,]{1}|["]{1}[\s]*[\}]{1}){1}/U';
        $match = preg_match($pattern, $this->fileContent, $matches, NULL, $this->cursor);
        if ($match) {
          // Move the cursor
          $this->cursor += strlen($matches[0]);

          return ([strtolower(str_replace(" ", "", $matches[1])), $matches[2]]);
        } else {
          return (FALSE);
        }
      }
    }
    return false;
  }

  /**
   * Fix formatting issues
   * @return void
   */
  private function fixFormatIssues()
  {
    // Let fix the ending of a bibtex item from "} }" to "}, }"
    $pattern = '/(((\}[\s]*\}[\s]*)@)|(\}[\s]*\}[\s]*$))/U';
    if (preg_match_all($pattern, $this->fileContent, $matches)) {
      $replaces = [];
      foreach ($matches[0] as $match) {
        $replaces[$match] = $match;
      }
      foreach ($replaces as $replace) {
        $replaceWith = str_replace(" ", "", $replace);
        $replaceWith = str_replace("}}", "},}", $replaceWith);
        $this->fileContent = str_replace($replace, $replaceWith, $this->fileContent);
      }
    }
    // Other encoding issues
    $this->fileContent = str_replace("``", '"', $this->fileContent);
  }
}