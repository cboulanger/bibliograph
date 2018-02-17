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
use app\models\UserConfig;

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
   * @return string
   */
  public function getVersion()
  {
    return $this->getNpmPackageData()->version;
  }
  
 /**
   * Returns the URL that sets the application into a specific state, showing a reference and selecting a folder
   * @param string $datasource
   * @param int $folderId
   * @param int $modelId
   */
  public function getAppStateUrl($datasource,$folderId,$modelId)
  {
    //https://demo.bibliograph.org/bibliograph/source/#datasource.database1!modelType.reference!itemView.referenceEditor!folderId.3
    notImplemented();
    return dirname(dirname(qcl_server_Server::getUrl() ) ) .
      "/build/#datasource.$datasource" .
      ( $folderId ? "!folderId.$folderId" : "" ).
      ( $modelId  ? "!modelType.reference!modelId.$modelId" : "");
  }    

  /**
   * Returns the url of the client application's build directory
   * @return string
   */
  public function getClientUrl()
  {
    notImplemented();
    return "http://" . $_SERVER["HTTP_HOST"] .
      dirname( dirname( $_SERVER["SCRIPT_NAME"] ) ) .
      "/build";
  }

  /**
   * Alias of qcl_server_Server::getUrl()
   * @return string
   */
  public function getServerUrl()
  {
    notImplemented();
    return qcl_server_Server::getUrl();
  } 
}
