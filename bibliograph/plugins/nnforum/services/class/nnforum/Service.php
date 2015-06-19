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

define("NNFORUM_PATH", "plugins/nnforum/services/www/");
define("NNFORUM_RELATIVE_PATH", "../" . NNFORUM_PATH);

class nnforum_Service
  extends qcl_data_controller_Controller
{
  public function method_getForumUrl()
  {
    $app = $this->getApplication();
    
    // set the number of read items to the current number of posts
    $count = $this->getPostCount();
    if( $count !== null )
    {
      $app->setPreference( "nnforum.readposts", $count );
    }
    
    // link
    $userModel = $app->getAccessController()->getActiveUser();
    $username = "";
    $password = "";
    if( ! $userModel->isAnonymous() )
    {
      $username = $userModel->getName();
      // create a hashed password from name and user id, so it cannot be
      // easily guessed, but is always the same
      $password = md5( $name . $userModel->id() );  
    }
    
    $forumLink = NNFORUM_RELATIVE_PATH . "?path=Forum/&username=$username&password=$password";
    header("location:" . $forumLink );
  }
  
  public function method_getUnreadPosts()
  {
    $count = $this->getPostCount();
    if ( $count === null ) {
      return 0;
    }
    $readItems = $this->getApplication()->getPreference( "nnforum.readposts" );
    $unreadItems = $count-$readItems;
    if( $unreadItems < 0 )
    {
      $this->getApplication()->setPreference( "nnforum.readposts", 0 );
      return 0;
    }
    return $unreadItems;
  }
  
  protected function getPostCount()
  {
    $rss_url = dirname( dirname( qcl_server_Server::getInstance()->getUrl() ) ) .
                "/". NNFORUM_PATH ."Forum/index.xml";
    $rss = @simplexml_load_file($rss_url);
    if( ! $rss ) return null;
    return count($rss->channel->item);
  }
}
