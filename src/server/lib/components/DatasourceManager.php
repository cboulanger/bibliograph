<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace lib\components;

use Yii;
use app\models\Config;
use app\models\User;
use app\models\Datasource;
use lib\components\ConsoleAppHelper as Console;

/**
 * Component class providing methods to create and migrate datasource tables,
 * i.e. model tables that have a common prefix and are meant to be used together to 
 * form a complex source of data
 * values
 */
class DatasourceManager extends \yii\base\Component
{
  /**
   * For backward-compatibility with v2, this map translates old schema names to 
   * new class names
   *
   * @var array
   */
  protected $legacySchema = [
    'bibliograph.schema.bibliograph2' => '\app\models\BibliographicDatasource'
  ];

  /**
   * Creates the tables necessary for a datasource, using migration files
   *
   * @param \app\models\Datasource $datasource
   * @return void
   */
  public function createModelTables( \app\models\Datasource $datasource )
  {
    $params = [
      'all',
      'migrationNamespaces' => 'app\\migrations\\schema\\datasource',
    ];
    $db = $datasource->getConnection();
    Console::runAction( 'migrate/up', $params, null, $db );
  }  

  /**
   * Checks if new migrations exist for the tables of the given datasource
   * schema class
   *
   * @param string $class Datasource schema class
   * @return void
   */
  public function checkNewMigrations( $class )
  {
    throw new BadMethodCallException("Not implemented");
  }

  /**
   * Migrates the tables of the datasources which are of the 
   * given schema class to the newest version
   * 
   * NOT TESTED!
   *
   * @param string $class Datasource schema class
   * @return boolean Returns true if migration succeeded
   */
  public function migrate( $class )
  {
    if( YII_ENV_PROD ){
      throw new \Exception('Datasource migrations are not allowed in production mode. Please contact the adminstrator');
    };

    // backward compatibitly 
    if ( isset($this->legacySchema[$class]) ){
      $class = $this->legacySchema[$class];
    }

    if( ! is_subclass_of( $class, Datasource::class ) ){
      throw new \InvalidArgumentException( $class . " is not a subclass of " . Datasource::class );
    }

    $datasources = Datasource::find()->where([ 'schema' => $class ])->all();
    foreach( $datasources as $datasource ){
      $params = [
        'all',
        'migrationNamespaces' => 'app\\migrations\\schema\\datasource',
      ];
      $db = $datasource->getConnection();
      Console::runAction( 'migrate/up', $params, null, $db );
    }
    return true; 
  }
}