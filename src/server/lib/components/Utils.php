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
  // function __construct($config){
  //   parent::__construct();
  // }

  /**
   * returns the version of the application
   * @return string
   */
  public function version()
  {
    return trim(file_get_contents(Yii::getAlias('@app/../version.txt')));
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


  //-------------------------------------------------------------
  // etc
  //-------------------------------------------------------------

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
