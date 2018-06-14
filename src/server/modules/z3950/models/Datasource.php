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

namespace app\modules\z3950\models;
use app\modules\z3950\lib\yaz\Result;
use Yii;

/**
 * Datasource model for z3950 datasources
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
  const SCHEMA_ID = "z3950";

  /**
   * A descriptive name (should be set)
   * @var string
   */
  static $name = "Z39.50 Datasource";

  /**
   * More detailed description of the Datasource (optional)
   * @var string
   */
  static $description = "A datasource that caches search results from queries to catalogues based on the Z39.50 protocol";

  /**
   * Override schema migration namespace
   * @return string
   */
  public function getMigrationNamespace()
  {
    return "\\app\\modules\\z3950\\migrations";
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
    return "z3950_";
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
   * Remove overridden function, since datasource has no folders
   */
  public function addDefaultFolders(){}
}
