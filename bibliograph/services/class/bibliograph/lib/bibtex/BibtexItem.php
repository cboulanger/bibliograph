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

/**
 *  Bibtex item description
 *  @author Frederick Giasson, Structured Dynamics LLC.
 */
class BibtexItem
{
  protected  $itemType = ""; // The Bibtex entry type.

  protected $itemID = ""; // The Bibtex entry ID.

  protected $properties = array();

  public function addType($type)
  {
    if( ! $type )
    {
      throw new InvalidArgumentException("Invalid type '$type'");
    }
    $this->itemType = $type;
  }

  public function addID($id)
  {
    if( ! $id )
    {
      throw new InvalidArgumentException("Invalid id '$id'");
    }
    $this->itemID = $id;
  }

  public function addProperty($property, $value)
  {
    if( ! $property )
    {
      throw new InvalidArgumentException("Invalid property '$property'");
    }
    if ( empty( $this->properties[$property] ) )
    {
      $this->properties[$property] = $value;
    }
    else
    {
      $this->properties[$property] .= "; " . $value;
    }

  }

  public function getItemType()
  {
    return $this->itemType;
  }

  public function getItemID()
  {
    return $this->itemID;
  }

  public function getProperties()
  {
    return $this->properties;
  }
}
?>