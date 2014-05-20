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

    return array();
  }
}

/*

<?xml version="1.0"?>
<zs:searchRetrieveResponse xmlns:zs="http://www.loc.gov/zing/srw/">
<zs:version>1.1</zs:version>
<zs:numberOfRecords>1</zs:numberOfRecords>
<zs:records>
<zs:record>
  <zs:recordSchema>info:srw/schema/1/dc-v1.1</zs:recordSchema>
  <zs:recordPacking>xml</zs:recordPacking>
  <zs:recordData>
  <srw_dc:dc xmlns:srw_dc="info:srw/schema/1/dc-schema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://purl.org/dc/elements/1.1/" xsi:schemaLocation="info:srw/schema/1/dc-schema http://www.loc.gov/standards/sru/resources/dc-schema.xsd">
  <title>CouchDB : the definitive guide /</title>
  <creator>Anderson, J. Chris.</creator>
  <creator>Lehnardt, Jan.</creator>
  <creator>Slater, Noah.</creator>
  <type>text</type>
  <publisher>Sebastopol, CA : O'Reilly,</publisher>
  <date>c2010.</date>
  <language>eng</language>
  <description>Includes index.</description>
  <subject>Database management.</subject>
  <identifier>URN:ISBN:9780596155896 (pbk.)</identifier>
  <identifier>URN:ISBN:0596155891 (pbk.)</identifier>
</srw_dc:dc></zs:recordData><zs:recordPosition>1</zs:recordPosition></zs:record></zs:records></zs:searchRetrieveResponse>
 */