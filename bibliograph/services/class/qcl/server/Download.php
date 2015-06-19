<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import("qcl_server_Upload");

/**
 * Download server.
 */
class qcl_server_Download
  extends qcl_server_Upload
{

  /**
   * Download a file as an attachment.
   * Expects the following GET or POST parameters:
   *    'download=true'
   *    'sessionId=d9dfjdkfsdfs...' The session id.
   *    'application=applicationNamespace'
   *    'datasource=nameOfDatasource'
   *    'id=/path/to/file/or/id' The id of the file or a path
   *    'name=nameOfTheFile' The name of the file as it is downloaded
   *     to the file.
   * There can be an optional 'delete=(true|false)' parameter if the file should
   * be deleted after download.
   * @return void
   */
  public function start()
  {

    $this->log("Starting download ...",QCL_LOG_REQUEST);

    qcl_assert_array_keys( $_REQUEST, array( 'sessionId', 'datasource','id', ) );

    $sessionId  = $_REQUEST['sessionId'];
    $datasource = $_REQUEST['datasource'];
    $filename   = $_REQUEST['id'];
    $delete     = false;

    if ( isset( $_REQUEST['delete'] ) )
    {
      $delete = qcl_parseBoolString( $_REQUEST['delete'] );
    }


    /*
     * authentication
     */
    if ( ! isset( $_REQUEST['sessionId'] ) )
    {
      $this->abort("Missing paramenter 'sessionId'");
    }
    $application = $this->getApplication();
    $accessController = $application->getAccessController();

    try
    {
      $userId = $accessController->getUserIdFromSession( $sessionId );
    }
    catch( qcl_access_InvalidSessionException $e )
    {
      /*
       * check http basic authentication
       */
      $username = $_SERVER['PHP_AUTH_USER'];
      $password = $_SERVER['PHP_AUTH_PW'];
      $userId = null;

      if ( $username and $password )
      {
        $userId = $accessController->authenticate( $username, $password );
      }
      if ( ! $userId )
      {
        header('WWW-Authenticate: Basic realm="Upload Area"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
      }
    }

    /*
     * Check active user
     */
    $userModel = $accessController->getUserModel();
    $userModel->load( $userId );
    $this->log(
      "Download authorized for " . $userModel->username() .
      "(Session #" . $sessionId . ").", QCL_LOG_REQUEST
    );

    /*
     * get datasource model
     */
    qcl_import("qcl_data_datasource_Manager") ;
    $dsController = qcl_data_datasource_Manager::getInstance();
    $dsModel = $dsController->getDatasourceModelByName( $datasource );
    if ( ! $dsModel->isFileStorage() )
    {
      $this->abort( "'$datasource' is not a file storage!");
    }

    /*
     * check access
     */
    if ( ! $dsModel->isActive() )
    {
      $this->abort( "Access to '$datasource' forbidden." );
    }

    // @todo alternative access control by using username/password

    /*
     * get file
     */
    $folder = $dsModel->getFolderObject();
    $file   = $folder->get( $filename );
    if ( ! $file->exists() )
    {
      $this->abort( sprintf( _( "File '%s' does not exist in datasource '%s'" ), $filename, $datasource ) );
    }

    /*
     * send headers
     */
    $name = isset( $_REQUEST['name'] ) ? $_REQUEST['name'] : $filename;
    $contentType = $this->getContentType( $name );
    $size = $file->size();

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: $contentType");
    header("Content-Disposition: attachment; filename=\"$name\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: $size" );

    /*
     * stream file content to client
     */
    $file->open("r");
    while ( $data = $file->read(8*1024) )
    {
      echo $data;
    }
    $file->close();

    /*
     * delete if requested
     */
    if ( $delete )
    {
      $file->delete();
    }

    exit;
  }

  /**
   * Returns the content type according to the file extension
   */
  protected function getContentType( $file )
  {
    $file_extension = strtolower(substr(strrchr($file,"."),1));
    switch( $file_extension )
    {
      case "pdf": $ctype="application/pdf"; break;
      case "txt": $ctype="text/plain"; break;
      case "exe": die("Not allowed");
      case "zip": $ctype="application/zip"; break;
      case "doc": $ctype="application/msword"; break;
      case "xls": $ctype="application/vnd.ms-excel"; break;
      case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
      case "gif": $ctype="image/gif"; break;
      case "png": $ctype="image/png"; break;
      case "jpg": $ctype="image/jpg"; break;
      default: $ctype="application/octet-stream";
    }
    return $ctype;
  }
}
