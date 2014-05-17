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
qcl_import("bibliograph_webapis_disambiguation_Name");

class bibliograph_plugin_isbnscanner_connector_Xisbn
implements bibliograph_plugin_isbnscanner_IConnector
{

  private $url = "http://xisbn.worldcat.org/webservices/xid/isbn/%s?method=getMetadata&format=json&fl=*";

  /**
   * Returns a description of the connector
   */
  public function getDescription()
  {
    return "xISBN Web Service";
  }

  /**
   * Returns the delimiter(s) that separates names in the output of this webservice
   * @return array
   */
  public function getNameSeparators()
  {
    return array(","," und ", " and ", ", and", " with ", "/");
  }

  /**
   * Returns the format in which names are returned as an integer value that is mapped to the
   * NAMEFORMAT_* constants
   * @return int
   */
  public function getNameFormat()
  {
    return NAMEFORMAT_AS_WRITTEN;
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
    $xIsbnUrl = sprintf( $this->url,$isbn );
    $json = qcl_server_getJsonContent($xIsbnUrl);
    $records = $json['list'];
    
    $data = array();

    // regular expressions to extract editor information
    $nameTypeRegExp = array(
      "editor" => array(
        "/(.+) \(Hg\.\)/",
        "/(.+) \(Hrsg\.\)/",
        "/hrsg\. von (.+)/i",
        "/hg\. von (.+)/i",
        "/^Hrsg\. (.+)$/",
        "/(.+) \(ed\.\)/i",
        "/(.+) \(eds\.\)/i",
        "/ed\. by (.+)/i",
        "/edited by (.+)/i",
      ),
      "translator" => array(
        "/aus dem (?:.+) von (.+)/i"
      )
    );

    foreach( $records as $record )
    {
      foreach( $record as $field => $value )
      {
        if( is_array($value) )
        {
          $record[$field] = join($value, BIBLIOGRAPH_VALUE_SEPARATOR );
        }
        // remove trailing periods
        $record[$field] = preg_replace("/((?:\.)+)$/","",$record[$field]);
      }

      $record['address']  = $record['city']; unset( $record['city'] );
      $record['edition']  = $record['ed'];   unset( $record['ed'] );
      $record['language'] = $record['lang']; unset( $record['lang'] );

      //AA (Audio), BA (Book), BB (Hardcover), BC (Paperback), DA (Digital),FA (Film or transparency), MA(Microform), VA(Video).
      $record['reftype'] = "book"; unset($record['form']);

      /// remove elipsis from author field
      $record['author']= str_replace("...","",$record['author']);

      // extract translator
      foreach($nameTypeRegExp['translator'] as $regExp )
      {
        if( preg_match($regExp, $record['author'], $matches ) )
        {
          $record['translator']= $matches[1];
          $record['author']= str_replace($matches[0],"",$record['author']);
        }
      }

      // check for edited volumes
      foreach($nameTypeRegExp['editor'] as $regExp )
      {
        if( preg_match($regExp, $record['author'], $matches ) )
        {
          $record['author']= $matches[1];
          $record['reftype'] = "collection";
        }
      }

      $record['author'] = preg_replace("/([.,;\s]+)$/","",$record['author']);

      $data[] = $record;
    }
    
    return $data;
  }
}