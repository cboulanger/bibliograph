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

namespace app\controllers;

use dto\ConfigLoadResult;
use InvalidArgumentException;
use Yii;

/**
 * Service class providing methods to get or set configuration
 * values
 */
class ConfigController extends \app\controllers\AppController
{

  //-------------------------------------------------------------
  // JSONRPC service methods
  //-------------------------------------------------------------

  /**
   * Service method to load config data
   * @param string|null $filter Filter
   * xxreturn \app\controllers\dto\ConfigLoadResult
   */
  public function actionLoad($filter = null)
  {
    return Yii::$app->config->getAccessibleKeys($filter);
  }

  /**
   * Service method to set a config value
   * @param string $key Key
   * @param mixed $value Value
   * @throws InvalidArgumentException
   * @return bool
   * @throws \lib\exceptions\Exception
   */
  function actionSet($key, $value)
  {
    // check key
    if (!Yii::$app->config->keyExists($key)) {
      throw new InvalidArgumentException("Configuration key '$key' does not exist");
    }
    if (!Yii::$app->config->valueIsEditable($key)) {
      throw new InvalidArgumentException("The value of configuration key '$key' is not editable");
    }

    if (Yii::$app->config->valueIsCustomizable($key)) {
      // if value is customizable, set the user variant of the key
      //$this->requirePermission("config.value.edit"); // even anonymous has this permission, therefore redundant.
      Yii::$app->config->setKey($key, $value);
    } else {

      // else, you need special permission to edit the default
      $this->requirePermission("config.default.edit");
      Yii::$app->config->setKeyDefault($key, $value);
    }
    return "OK";
  }

  /**
   * Service method to get a config value
   * @param string $key Key
   * @throws InvalidArgumentException
   * @return mixed
   */
  function actionGet($key)
  {
    // check key
    if (!Yii::$app->config->keyExists($key)) {
      throw new InvalidArgumentException(Yii::t('app', "Configuration key '$key' does not exist"));
    }
    return Yii::$app->config->getKey($key);
  }
}
