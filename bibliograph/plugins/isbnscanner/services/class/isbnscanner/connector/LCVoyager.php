<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("isbnscanner_IConnector");

class isbnscanner_connector_LCVoyager
implements isbnscanner_IConnector
{

  private $url = "http://z3950.loc.gov:7090/voyager?version=1.1&operation=searchRetrieve&query=bath.isbn=%s&maximumRecords=1&recordSchema=dc";

  /**
   * Returns a description of the connector
   */
  public function getDescription()
  {
    return "Library of Congress Voyager Webservice";
  }

  /**
   * Returns the delimiter(s) that separates names in the output of this webservice
   * @return array
   */
  public function getNameSeparators()
  {
    return array("; ");
  }

  /**
   * Returns the format in which names are returned as an integer value that is mapped to the
   * NAMEFORMAT_* constants
   * @return int
   */
  public function getNameFormat()
  {
    return NAMEFORMAT_SORTABLE_FIRST;
  }

  private function getTextContent( $xml, $path )
  {
    $elemArr = $xml->xpath($path);
    if( $elemArr === false ) return false;
    return $this->removeCruft( (string) array_pop( $elemArr ) );
  }

  private function getArrTextContent( $xml, $path )
  {
    $elemArr = $xml->xpath($path);
    if( $elemArr === false ) return false;
    $contents=array();
    foreach( $elemArr as $elem )
    {
      $contents[]= $this->removeCruft( (string) $elem);
    }
    return $contents;
  }

  private function removeCruft( $text )
  {
    return preg_replace("/[\.\/,]$/", "", $text);
  }

  private function getTextBefore($separator, $text )
  {
    $parts = explode($separator, $text);
    return $parts[0];
  }

  private function getTextAfter($separator, $text )
  {
    $parts = explode($separator, $text);
    return $parts[1];
  }

  /**
   * given an isbn, returns reference data
   * @param string $isbn
   *  ISBN string
   * @return array
   *  Array of associative arrays, containing records matching the isbn with
   *  BibTeX field names. Returns empty array if no match.
   * @throws qcl_server_IOException
   */ 
  public function getDataByIsbn( $isbn )
  {
    $url = sprintf( $this->url,$isbn );

    $xml = qcl_server_getXmlContent($url);
    $xml->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");

    $title = $this->getTextContent($xml, "//dc:title");
    if( $title === false or ! trim($title))
    {
      return array();
    }

    $record = array(
      "reftype"   => "book",
      "title"     => $title,
      "author"    => implode(BIBLIOGRAPH_VALUE_SEPARATOR, $this->getArrTextContent($xml, "//dc:creator") ),
      "publisher" => $this->getTextAfter(" : ", $this->getTextContent($xml, "//dc:publisher") ),
      "address"   => $this->getTextBefore(" : ", $this->getTextContent($xml, "//dc:publisher") ),
      "year"      => str_replace("c", "", $this->getTextContent($xml,"//dc:date")),
      "language"  => $this->getTextContent($xml,"//dc:language"),
      "keywords"  => implode(BIBLIOGRAPH_VALUE_SEPARATOR, $this->getArrTextContent($xml, "//dc:subject") ),
      "isbn"      => $isbn
    );

    //qcl_log_Logger::getInstance()->log(print_r($record,true),"warn");

    return array($record);
  }
}