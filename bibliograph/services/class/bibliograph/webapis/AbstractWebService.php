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

qcl_import("bibliograph_webapis_IWebApi");

abstract class bibliograph_webapis_AbstractWebService
    implements bibliograph_webapis_IWebApi
{

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $description;

  /**
   * @var array
   */
  protected $categories = array();

  /**
   * Returns the name of the Web API
   * @return string
   */
  function getName()
  {
    return $this->name;
  }

  /**
   * Returns the description of the Web API
   * @return string
   */
  function getDescription()
  {
    return $this->description;
  }

  /**
   * Returns one or several categories this API belongs to
   * @return array
   */
  function getCategories()
  {
    return $this->categories;
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
      throw new qcl_server_IOException( "Problem contacting %s.", $this->getDescription() );
    }
    return $xml;
  }
}