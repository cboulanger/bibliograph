<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("bibliograph_plugin_isbnscanner_IConnector");

class bibliograph_plugin_isbnscanner_connector_LCVoyager
implements bibliograph_plugin_isbnscanner_IConnector
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

  /**
   * given an isbn, returns reference data
   * @param string $isbn
   *  ISBN string
   * @return array 
   *  Array of associative arrays, containing records matching the isbn with
   *  BibTeX field names
   */ 
  public function getDataByIsbn( $isbn )
  {
    $url = sprintf( $this->url,$isbn );
    $xml = qcl_server_getXmlContent($url);
    $xml->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
    
    $record = array();
    $record['title'] = (string) array_pop( $xml->xpath("//dc:title"));
    
    return array();
  }
}