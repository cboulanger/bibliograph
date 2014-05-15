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

class bibliograph_webapis_disambiguation_WorldCatIdentities
extends bibliograph_webapis_disambiguation_Name
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
   * If the name is unique, return the normalized version (Lastname, Firstname).
   * if there is no exact match, return an array of possible names
   * @param string $name
   * @return string|array
   */
  function getNormalizedName($name)
  {
    $xml = $this->getXmlContent( $this->url . $name );
    $node = $xml->xpath("match[@type='ExactMatches']");
    if( is_array($node) && count($node) )
    {
      return (string) $node[0]->establishedForm;
    }
    return $name;
  }
}