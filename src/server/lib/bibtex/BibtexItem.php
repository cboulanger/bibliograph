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

use InvalidArgumentException;


/**
 *  Bibtex item description
 * @author Frederick Giasson, Structured Dynamics LLC.
 */
class BibtexItem
{
  /**
   * The Bibtex entry type
   * @var string
   */
  protected $itemType = "";

  /**
   * The Bibtex entry ID
   * @var string
   */
  protected $itemID = "";

  /**
   * The entry field properties
   * @var array
   */
  protected $properties = [];

  /**
   * @param string $type
   */
  public function addType(string $type)
  {
    if (!$type) {
      throw new InvalidArgumentException("Invalid type '$type'");
    }
    $this->itemType = $type;
  }

  /**
   * @param $id
   */
  public function addID(string $id)
  {
    if (!$id) {
      throw new InvalidArgumentException("Invalid id '$id'");
    }
    $this->itemID = $id;
  }

  /**
   * @param string $property
   * @param string $value
   */
  public function addProperty(string $property, string $value)
  {
    if (!$property) {
      throw new InvalidArgumentException("Invalid property '$property'");
    }
    if (empty($this->properties[$property])) {
      $this->properties[$property] = $value;
    } else {
      $this->properties[$property] .= "; " . $value;
    }
  }

  /**
   * @return string
   */
  public function getItemType() : string
  {
    return $this->itemType;
  }

  /**
   * @return string
   */
  public function getItemID() : string
  {
    return $this->itemID;
  }

  /**
   * @return array
   */
  public function getProperties() : array
  {
    return $this->properties;
  }
}