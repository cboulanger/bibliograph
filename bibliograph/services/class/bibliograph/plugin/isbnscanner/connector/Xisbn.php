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

class bibliograph_plugin_isbnscanner_connector_Xisbn
implements bibliograph_plugin_isbnscanner_IConnector
{
  
  /**
   * Returns a description of the connector
   */
  public function getDescription()
  {
    return "xISBN Web Service";
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
    $xisbnUrl = sprintf(
      "http://xisbn.worldcat.org/webservices/xid/isbn/%s?method=getMetadata&format=json&fl=*",
      $isbn
    );
    
    $result = file_get_contents($xisbnUrl);
    $json   = json_decode($result , true );
    $records = $json['list'];
    
    $data = array();
    foreach( $records as $record )
    {
      $record['address']  = $record['city']; unset( $record['city'] );
      $record['edition']  = $record['ed'];   unset( $record['ed'] );
      $record['language'] = $record['lang']; unset( $record['lang'] );
      $data[] = $record;
    }
    
    return $data;
  }
}