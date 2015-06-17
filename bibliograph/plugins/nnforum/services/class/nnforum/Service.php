<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("qcl_data_controller_Controller");

class nnforum_Service
  extends qcl_data_controller_Controller
{
  public function method_getForumUrl()
  {
    @mkdir(QCL_VAR_DIR . "/nnforum_users");
    $app = $this->getApplication();
    $userModel = $app->getAccessController()->getActiveUser();
    $username = "";
    $password = "";
    if( ! $userModel->isAnonymous() )
    {
      $username = $userModel->getName();
      $password = md5( $name . $userModel->id() . $userModel->getEmail() );  
    }
    
    $forumLink = basename( qcl_server_Server::getUrl() ) .
      "/../../plugins/nnforum/services/www/Forum?path=Forum/&username=$username&password=$password";
    header("location:" . $forumLink );
  }
}
