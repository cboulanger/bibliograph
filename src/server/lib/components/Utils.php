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

use app\controllers\AppController;
use Yii;
use lib\exceptions\UserErrorException;

/**
 * Class Utils
 * @package lib\components
 * @todo rename?
 * @property string $version
 */
class Utils extends \yii\base\Component
{

  /**
   * @return string
   */
  public function getJsonRpcEndpoint()
  {
    return dirname(Yii::$app->request->absoluteUrl);
  }

  /**
   * Returns the url to the yii2 server
   * @param string $route
   * @param array|object $params
   * @return string
   */
  public function makeUrl($route, $params) {
    $url = Yii::$app->request->baseUrl;
    return $url . "/" . $route . (strstr($url, "?") ? "&" : "?") . http_build_query($params);
  }

  /**
   * Returns the URL of the HTML user interface
   */
  public function getFrontendUrl()
  {
    return Yii::$app->request->referrer;
  }

  /**
   * Returns the content of package.json as an object structure
   * @return object
   */
  public function getNpmPackageData()
  {
    $package_json_path = APP_ROOT_DIR .DIRECTORY_SEPARATOR . "package.json";
    return json_decode( file_get_contents( $package_json_path ) );
  }


  /**
   * Returns the version of the application
   * @return string
   */
  public function getVersion()
  {
    $versionTxtPath = APP_ROOT_DIR . DIRECTORY_SEPARATOR . "version.txt";
    if (file_exists($versionTxtPath)) {
      return trim(file_get_contents($versionTxtPath));
    }
    try{
      return trim($this->getNpmPackageData()->version);
    } catch( \Exception $e){
      throw new UserErrorException("Cannot read package.json or version.txt",null, $e);
    }
  }

  /**
   * Set the best language based on browser request.
   */
  public function setLanguageFromBrowser() {
    Yii::$app->language = Yii::$app->request->getPreferredLanguage($this->getLanguages());
  }

  /**
   * Return the languages supported by the application by scanning the 'messages' dir
   * "en-US" is always included.
   * @return array
   */
  public function getLanguages()
  {
    static $languages = null;
    if( is_null($languages) ){
      $languages=[Yii::$app->sourceLanguage];
      foreach (scandir(__DIR__ . "/../../messages") as $dir) {
        if( $dir[0] !== "." ) {
          $languages[] = $dir;
        }
      }
      $languages=array_unique($languages);
    }
    return $languages;
  }
}
