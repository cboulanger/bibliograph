<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("bibliograph_webapis_AbstractWebService");

/**
 * Class bibliograph_webapis_identity_SimpleNameParser
 * This is a primitive name parser which takes the last word of the string as the last name,
 * appends a comma, and then the rest of the string. This works only for the majority of english and
 * German names, but not for non-western or compound names. Must be replaced by a more sophisticated
 * solution.
 */
class bibliograph_webapis_identity_SimpleNameParser
extends bibliograph_webapis_AbstractWebService
{

  /**
   * @var string
   */
  protected $name = "Simple Name Parser";

  /**
   * @var array
   */
  protected $categories = array("identity","disambiguate-name");

  /**
   * If the name is unique, return the sortable version, normally: last/family name, first name(s).
   * If there is no exact match, return an array of possible names.
   * If no match exists, return false
   * @param string $name
   * @return false|string|array
   */
  function getSortableName($name)
  {
    $parts = explode(" ", $name );
    return array_pop($parts) . ", " . implode(" ", $parts);
  }
}