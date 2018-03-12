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
   * A descriptive name (should be set)
   * @var string
   */
  static $name = "Z39.50 Datasource";

  /**
   * More detailed description of the Datasource (optional)
   * @var string
   */
  static $description = "A datasource that connects to catalogues based on the Z39.50 protocol";

  /**
   * Override schema migration namespace
   * @var string
   */
  static $migrationNamespace = "\app\modules\z3950\migrations";

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
    $this->addModel( 'search', Seach::class,  'search');
    $this->addModel( 'result', Result::class, 'result');
  }

  /**
   * Remove overridden function, since datasource has no folders
   */
  public function addDefaultFolders(){}
}
