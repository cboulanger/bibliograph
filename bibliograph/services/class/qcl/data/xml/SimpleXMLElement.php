<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
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
 * Class extending SimpleXMLElement,
 * adding helper methods.
 */
class qcl_data_xml_SimpleXMLElement
  extends SimpleXMLElement
{
  /**
   * Returns a new instance of a document with a root node
   * @return qcl_data_xml_SimpleXMLElement
   */
  static public function createDocument()
  {
    return new self("<?xml version='1.0' standalone='yes'?><root/>");
  }

  /**
   * Returns a new xml object from a string
   * @param string $string
   * @throws qcl_data_xml_Exception
   * @return qcl_data_xml_SimpleXMLElement
   */
  static public function createFromString( $string )
  {
    libxml_use_internal_errors(true);
    $xmlDoc = simplexml_load_string( $string, "qcl_data_xml_SimpleXMLElement" );
    if ( ! $xmlDoc )
    {
      $msg = "Errors while parsing XML:";
      foreach( libxml_get_errors() as $error)
      {
        $msg .= "\n" . $error->message;
      }
      throw new qcl_data_xml_Exception($msg);
    }
    libxml_use_internal_errors( false );
    return $xmlDoc;
  }

  /**
   * Returns a new xml object from a file
   * @param string $file
   * @throws qcl_data_xml_Exception
   * @throws InvalidArgumentException
   * @return qcl_data_xml_SimpleXMLElement
   */
  static public function createFromFile( $file )
  {
    libxml_use_internal_errors(true);

    if( is_qcl_file( $file ) )
    {
      $file = qcl_realpath( $file->filePath() );
    }
    else
    {
      throw new InvalidArgumentException("Loading XML from remote hosts not yet implemented.");
    }

    $xmlDoc =  simplexml_load_file( $file, "qcl_data_xml_SimpleXMLElement" );
    if ( ! $xmlDoc )
    {
      $msg = "Errors while parsing XML from '$file':";
      foreach( libxml_get_errors() as $error)
      {
        $msg .= "\n" . $error->message;
      }
      throw new qcl_data_xml_Exception($msg);
    }
    libxml_use_internal_errors( false );
    return $xmlDoc;
  }

  /**
   * Adds a child element to the XML node
   * @link http://php.net/manual/en/function.simplexml-element-addChild.php
   * @param $name string  The name of the child element to add.
   * @param $value string[optional]  If specified, the value of the child element.
   * @param $namespace string[optional]  If specified, the namespace to which the child element belongs.
   * @return qcl_data_xml_SimpleXMLElement
   */
  public function addChild ($name, $value = null, $namespace = null)
  {
    return parent::addChild ($name, $value, $namespace );
  }

  /**
   * Adds an attribute to the SimpleXML element
   * @link http://php.net/manual/en/function.simplexml-element-addAttribute.php
   * @param $name string  The name of the attribute to add.
   * @param $value string The value of the attribute.
   * @param $namespace string[optional] If specified, the namespace to which the attribute belongs.
   * @return void
   */
  public function addAttribute ($name, $value, $namespace = null)
  {
    parent::addAttribute ($name, $value, $namespace);
  }

  /**
   * Returns the given attribute of the node
   * @param $name
   * @param $default
   * @return mixed
   */
  public function getAttribute($name, $default='')
  {
    $attrs = (array) $this->attributes();
    if (isset($attrs[$name]))
    {
      return (string) $attrs[$name];
    }
    return (string) $default;
  }

  /**
   * Returns the number of attributes
   * @return integer
   */
  public function getAttributeCount()
  {
    return (int) count( (array) $this->attributes() );
  }

  /**
   * Returns true if the node has attributes
   * @return bool
   */
  public function hasAttributes()
  {
    return (bool) $this->getAttributeCount();
  }

  /**
   * Returns the CDATA of the node
   * @return string
   */
  public function CDATA()
  {
    return (string) $this;
  }

  /**
   * Sets the CDATA of the node
   * @param $data
   */
  public function setCDATA( $data )
  {
    $this->{0} = $data;
  }

  /**
   * Remove a child from the node.
   * @param object|\SimpleXMLElement $childNode
   * @return boolean true if node was removed
   */
  public function removeChild( SimpleXMLElement $childNode )
  {
    $found = false;
    foreach ( $this->children() as $node )
    {
      if ( $node === $childNode )
      {
        unset( $node );
        $found = true;
        break;
      }
    }
    return $found;
  }

  /**
   * Finds children of given node
   * @link http://php.net/manual/en/function.simplexml-element-children.php
   * @param null|string $ns
   * @param bool|null $is_prefix
   * @internal param string $ns [optional]
   * @internal param bool $is_prefix [optional] Default to false
   * @return SimpleXMLElement
   */
  public function children ($ns = null, $is_prefix = null)
  {
    return parent::children( $ns, $is_prefix );
  }

  /**
   * Return a well-formed XML string based on SimpleXML element
   * @link http://php.net/manual/en/function.simplexml-element-asXML.php
   * @param null|string $filename
   * @param bool $pretty
   * @internal param string $filename [optional]  If specified, the function writes the data to the file rather than
   * returning it.
   * @return mixed If the filename isn't specified, this function
   * returns a string on success and false on error. If the
   * parameter is specified, it returns true if the file was written
   * successfully and false otherwise.
   */
  public function asXML ($filename = null, $pretty=false )
  {
    if ( $pretty )
    {
      $dom = dom_import_simplexml($this)->ownerDocument;
      $dom->formatOutput = true;
      $xml = $dom->saveXML();
      if ( $filename )
      {
        file_put_contents( $filename, $xml );
      }
      else
      {
        return $xml;
      }
    }

    if ( $filename )
    {
      return parent::asXML( $filename );
    }

    return parent::asXML();
  }

}

