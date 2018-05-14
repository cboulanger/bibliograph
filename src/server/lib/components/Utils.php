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
   * Returns the absolute path to the project's root
   * directory
   *
   * @return string
   */
  public function getProjectRootDir()
  {
    return realpath( __DIR__ . "/../../../.." );
  }

  /**
   * Returns the content of package.json as an object structure
   * @return object
   */
  public function getNpmPackageData()
  {
    $package_json_path = $this->getProjectRootDir() . "/package.json";
    return json_decode( file_get_contents( $package_json_path ) );
  }


  /**
   * Returns the version of the application
   * @todo Rewrite in "production first" mode
   * @return string
   */
  public function getVersion()
  {
    try{
      return trim($this->getNpmPackageData()->version);
    } catch( \Exception $e){
      try{
        return trim(file_get_contents(__DIR__ . "/../../../version.txt"));
      } catch( \Exception $e) {
        throw new UserErrorException("Cannot read package.json or version.txt",null, $e);
      }
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
