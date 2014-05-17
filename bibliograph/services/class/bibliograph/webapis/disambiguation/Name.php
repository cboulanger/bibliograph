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

abstract class bibliograph_webapis_disambiguation_Name
{
  /**
   * @var string The default service
   */
  static $defaultService = "WorldCatIdentities";

  /**
   * Create an instance of the default or particular service object
   * @param string|null $service The name of the service
   * @return bibliograph_webapis_disambiguation_Name
   */
  static function createInstance($service=null)
  {
    $service = $service ? $service : self::$defaultService;
    $clazz = "bibliograph_webapis_disambiguation_$service";
    qcl_import($clazz);
    return new $clazz;
  }

  /**
   * @var string
   */
  protected $description;

  /**
   * @return string
   * @throws LogicException
   */
  public function getDescription()
  {
    if ( ! $this->description ){
      throw new LogicException(__CLASS__ . " has no description");
    }
    return $this->description;
  }

  /**
   * @param string $url
   * @return SimpleXMLElement
   * @throws qcl_server_IOException
   */
  protected function getXmlContent($url)
  {
    try
    {
      $xml = qcl_server_getXmlContent($url);
    }
    catch ( qcl_server_IOException $e)
    {
      throw new qcl_server_IOException( $this->tr( "Problem contacting %s.", $this->getDescription() ) );
    }
    return $xml;
  }

  /**
   * If the name is unique, return the sortable version, normally: last/family name, first name(s).
   * If there is no exact match, return an array of possible names.
   * If no match exists, return false
   * @param string $name
   * @return false|string|array
   */
  abstract function getSortableName($name);
}