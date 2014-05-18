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

qcl_import("bibliograph_webapis_identity_AbstractIdentity");

class bibliograph_webapis_identity_WorldCatIdentities
extends bibliograph_webapis_identity_AbstractIdentity
{
  /**
   * @var string
   */
  protected $description = "WorldCat Identities Webservice";

  /**
   * @var string
   */
  private $url = "http://www.worldcat.org/identities/find?fullName=";

  /**
   * If the name is unique, return the sortable version, normally: last/family name, first name(s).
   * If there is no exact match, return an array of possible names.
   * If no match exists, return false
   * @param string $name
   * @return false|string|array
   */
  function getSortableName($name)
  {
    $url = $this->url . urlencode($name);
    $xml = $this->getXmlContent( $url );
    $node = $xml->xpath("match[@type='ExactMatches']");
    if( is_array($node) && count($node) )
    {
      // todo: return similar names as doc sais
      $sortableName = trim ($node[0]->establishedForm);
      if ( ! empty( $sortableName ) )
      {
        // remove biographic information
        $sortableName = trim(preg_replace("/[0-9]{4}\w*-\w*[0-9]{4}/","",$sortableName));
        return $sortableName;
      }
    }
    return false;
  }
}