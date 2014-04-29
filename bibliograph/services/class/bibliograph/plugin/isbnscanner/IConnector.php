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

interface bibliograph_plugin_isbnscanner_IConnector
{
  /**
   * Returns a description of the connector
   */
  public function getDescription();
  
  /**
   * given an isbn, returns reference data
   * @param string $isbn
   *  ISBN string
   * @return array 
   *  Array of associative arrays, containing records matching the isbn with
   *  BibTeX field names
   */ 
  public function getDataByIsbn( $isbn );
}