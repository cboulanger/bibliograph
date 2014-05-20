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

/**
 * constants
 */
const NAMEFORMAT_AS_WRITTEN = 0;
const NAMEFORMAT_SORTABLE_FIRST = 1;

/**
 * Interface bibliograph_plugin_isbnscanner_IConnector
 */
interface bibliograph_plugin_isbnscanner_IConnector
{
  /**
   * Returns a description of the connector
   */
  public function getDescription();

  /**
   * Returns the delimiter(s) that separates names in the output of this webservice
   * @return array
   */
  public function getNameSeparators();

  /**
   * Returns the format in which names are returned as an integer value that is mapped to the
   * NAMEFORMAT_* constants
   * @return int
   */
  public function getNameFormat();

  /**
   * given an isbn, returns reference data
   * @param string $isbn
   *  ISBN string
   * @return array 
   *  Array of associative arrays, containing records matching the isbn with
   *  BibTeX field names. Returns empty array if no match.
   */ 
  public function getDataByIsbn( $isbn );
}