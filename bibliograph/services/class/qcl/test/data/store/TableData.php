<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2009 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

require "qcl/test/AbstractStore.php";

class class_TableData extends AbstractStore
{

  function test_resetRowCount( $params )
  {
    $_SESSION['rowCount'] = rand(1000,9000);  
    return array(
      'rowCount'  => $_SESSION['rowCount'],
      'statusText' => "Table has {$_SESSION['rowCount']} rows."
    );
  }  
  
  function test_getRowCount( $params )
  {
    if ( ! isset( $_SESSION['rowCount'] ) )
    {
      $_SESSION['rowCount'] = rand(1000,9000);  
    }
    return array(
      'rowCount'  => $_SESSION['rowCount'],
      'statusText' => "Table has {$_SESSION['rowCount']} rows."
    );
  }
  
  /**
   * get node data
   * @param array $params
   */
  function test_getRowData( $params )
  {

    list( $firstRow, $lastRow, $requestId, $rowIds, $node ) = $params;
    
    $rowData = array();
    $rowIds = array( "id", "text", "boolean", "number", "date" );
    for ( $i= $firstRow; $i<= $lastRow; $i++ )
    {
      $row = array();
      foreach( $rowIds as $rowId )
      {
        switch( $rowId )
        {
          case "id": 
            $value = (int) $i;
            break;
            
          case "number": 
            if ( $node and isset( $node->id ) )
            {
              $value = $node->id + $i; 
            }
            else
            {
              $value = $_SESSION['rowCount']-$i;
            }
            break;
            
          case "date":
            $day = rand(1,30);
            $month = rand(1,12);
            $year = rand(1970,2009);
            $value = "$day.$month.$year";
            break;
            
          case "boolean":
            $value = (bool) ($i % 2);
            break;
            
          case "text":
            if ( $node and isset( $node->label ) )
            {
              $value = "{$node->label}, Row $i"; 
            }
            else
            {
              $value = "Row $i";  
            }
            break;
        }
        $row[$rowId]=$value;
      }
      $rowData[] = $row;
    }
    
    $statusText = "Retrieved  rows $firstRow - $lastRow of {$_SESSION['rowCount']}.";
    
    /*
     * return data to client
     */
    return array(
      'requestId'  => $requestId,
      'rowData'    => $rowData,   
      'statusText' => $statusText
    );
  }
  
  function test_addRow( )
  {
    $_SESSION['rowCount']++;
    return array(
      'statusText' => "Row added."
    );    
  }
  

}
