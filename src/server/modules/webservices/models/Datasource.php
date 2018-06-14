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

namespace app\modules\webservices\models;
use app\modules\webservices\repositories\IConnector;
use lib\exceptions\UserErrorException;
use Yii;

/**
 * Datasource model for webservices datasources
 *
 * Dependencies:
 * - php_yaz extension
 */

class Datasource
  extends \app\models\Datasource
  implements \lib\schema\ISchema
{

  /**
   * The named id of the datasource schema
   */
  const SCHEMA_ID = "webservices";

  /**
   * A descriptive name (should be set)
   * @var string
   */
  static $name = "Webservice datasource";

  /**
   * More detailed description of the Datasource (optional)
   * @var string
   */
  static $description = "A datasource that caches search results from bibliographic webservices such as CrossRef";

  /**
   * Override schema migration namespace
   * @return string
   */
  public function getMigrationNamespace()
  {
    return "\\app\\modules\\webservices\\migrations";
  }

  /**
   * @todo
   * @return string
   */
  public function getTableModelType()
  {
    return "record";
  }

  /**
   * Returns static string, so that all results are stored in one table
   * @param $datasourceName
   * @return string
   */
  public static function createTablePrefix($datasourceName){
    return "webservices_";
  }

  /**
   * Initialize the datasource, registers the models
   * @throws \InvalidArgumentException
   */
  public function init()
  {
    parent::init();
    $this->addModel( 'record', Record::class, 'record');
    $this->addModel( 'search', Search::class,  'search');
  }

  /**
   * Remove overridden function, since this datasource has no folders
   */
  public function addDefaultFolders(){}

  /**
   * Returns the connector for the given datasource
   * @param string $namedId
   * @return IConnector
   */
  public function createConnector( string $namedId )
  {
    $class = "\\app\\modules\\webservices\\connectors\\" . ucfirst($namedId);
    if( ! class_exists($class)) throw new \InvalidArgumentException("Connector '$namedId' does not exist.");
    return new $class();
  }
}
