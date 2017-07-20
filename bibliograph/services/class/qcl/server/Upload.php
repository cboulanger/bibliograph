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

qcl_import("qcl_application_Application");
qcl_import("qcl_server_JsonRpcRestServer");

/**
 * Uploads a one or more files to the system temporary folder.
 * Authentication an be done in two different ways:
 * 1) The calling client provides a valid session id in the URL
 *    ("?sessionId=a8dab9das...") or in the request parameter 'sessionId'.
 * 2) If this is not provided, the server responds by presenting a http
 *    authentication request.
 *    
 * You also need to provide a 'application' parameter to let the server know
 * for which application the upload should be processed. 
 * 
 * If you want to call a json-rpc method after the upload, add "service",
 * "method", "params" as parameters. The given method will be called with  
 * the given parameters as in the qcl_server_JsonRpcRestServer class,
 * with the difference that the paths to the uploaded files and the file names 
 * are added at the end of the request parameters (or added as only parameters 
 * if no other parameters are given). The "application" parameter is not 
 * neccessary with an accompanying rpc call. 
 * 
 * Otherwise, the script returns a HTML string as response. If successful, 
 * the response is a SPAN element with the qcl_file attribute containing
 * the path to the uploaded file. Otherwise, it is a SPAN element
 * with an attribute 'qcl_error' set to true, and containing the error
 * message.
 */
class qcl_server_Upload
  extends qcl_server_JsonRpcRestServer
{

   private $rpcRequest=false;
   
   private $filePaths = array();
   
   private $fileNames = array();
   

  /**
   * @override
   */
  public function start()
  {
    $this->log("Starting upload ...",QCL_LOG_REQUEST);
    
    /*
     * is there a connected RPC request?
     */
    if ( isset( $_REQUEST['method'] ) )
    {
      $this->rpcRequest = true;
    }    

    /*
     * check if upload directory is writeable
     */
    if ( ! is_writable( QCL_UPLOAD_PATH ) )
    {
      $this->logError( sprintf(
        "Upload path '%s' is not writeable.", QCL_UPLOAD_PATH
      ) );
      $this->abort("Upload path is not writable");
    }

    /*
     * authentication
     */
    if ( ! isset( $_REQUEST['sessionId'] ) and ! isset( $_REQUEST['QCLSESSID'] )  )
    {
      $this->abort("Missing session id");
    }
    $application = $this->getApplication();
    $accessController = $application->getAccessController();
    $sessionId = either($_REQUEST['sessionId'], $_REQUEST['QCLSESSID']);
    
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
    if ( $userModel->isAnonymous() )
    {
      $this->abort( "Anonymous uploads are not permitted.");
    }

    $this->log(
      "Upload authorized for " . $userModel->username() .
      "(Session #" . $sessionId . ").", QCL_LOG_REQUEST
    );
    
    
    /*
     * maximal file upload
     */
    $maxSize = min( array(
       QCL_UPLOAD_MAXFILESIZE * 1024, 
       qcl_parse_filesize( ini_get("upload_max_filesize" ) )
    ) );

    /*
     * handle all received fields
     */
    $files = $_FILES;
    foreach ( $files as $file )
    {
      /*
       * convert string fields into array
       */
      if ( ! is_array( $file['name']) )
      {
        foreach( $file as $key => $value )
        {
          $file[$key] = array( $value );
        }
      }
      
      /*
       * handle all received files
       */
       for( $i=0; $i<count( $file['name'] ); $i++ )
       {
         
        /*
         * check file size
         */
        if ( $file['size'][$i] > $maxSize )
        {
           $this->abort( sprintf( "File '%s' (%s) exceeds maximum filesize of %s", 
             $file['name'][$i], 
             qcl_format_filesize( $file['size'][$i] ),
             qcl_format_filesize( $maxSize )
           ) );
        }
        elseif ( $file['size'][$i] == 0 )
        {
          $this->abort( sprintf( "Problem with file '%s' (probably exceeds maximum filesize of %s)", 
             $file['name'][$i], 
             qcl_format_filesize( $maxSize )
           ) );
        }
        
        /*
         * get file info
         */
        $tmp_name  = $file['tmp_name'][$i];
        $file_name = $file['name'][$i];
  
        /*
         * check file name for validity
         */
        if ( strstr($file_name, ".." ) )
        {
          $this->abort( "Illegal filename." );
        }
        
        /*
         * strip illegal characters from file name
         */
  
        /*
         * target path
         */
        $hash = md5( $sessionId . $file_name . microtime_float() );
        $tgt_path  = QCL_UPLOAD_PATH . "/$hash";
        $this->log( "Moving uploaded file to '$tgt_path' ..." );
  
        /*
         * check if file exists and delete it 
         */
        if ( file_exists ( $tgt_path) )
        {
           unlink ( $tgt_path );
        }
  
        /*
         * move temporary file to target location and check for errors
         */
        if ( ! move_uploaded_file( $tmp_name, $tgt_path ) or
             ! file_exists( $tgt_path ) )
        {
          $this->log( "Problem saving the file to '$tgt_path'.", QCL_LOG_REQUEST );
          $this->echoWarning( "Problem saving file '$file_name'." );
        }
  
        /*
         * report upload succes
         */
        else
        {
          $this->echoReply( "<span qcl_file='$tgt_path'>Upload of '$file_name' successful.</span>" );
          $this->log("Uploaded file to '$tgt_path'", QCL_LOG_REQUEST);
          $this->fileNames[] = $file_name;
          $this->filePaths[] = $tgt_path;
        }
      }
    }
    
    if( $this->rpcRequest )
    {
      /*
       * start REST json-rpc server
       */
      parent::start();
    }
    else 
    {
      /*
       * end of script
       */
      exit;
    }
  }

  /**
   * Returns the current application or false if no application exists.
   * @throws qcl_InvalidClassException
   * @return qcl_application_Application|false
   */
  public function getApplication()
  {
    /*
     * if this is an upload with rpc request, we can use the
     * parent class' method.
     */
    if( $this->rpcRequest )
    {
      $request = qcl_server_Request::getInstance();
      $request->service = $_REQUEST['service'];
      return parent::getApplication();
    }
    
    /*
     * otherwise, we need a hint from the request data
     */
    if ( ! isset( $_REQUEST['application'] ) )
    {
      $this->abort("Missing paramenter 'application'");
    }

    $service = new QclString( $_REQUEST['application'] );
    $appClass = (string) $service
      ->replace("/\./","_")
      ->concat( "_Application" );

    try
    {
      /*
       * import class file
       */
      qcl_import( $appClass );

      /*
       * instantiate new application object
       */
      $app = new $appClass;
      if ( ! $app instanceof qcl_application_Application )
      {
        throw new qcl_InvalidClassException(
          "Application class '$appClass' must be a subclass of 'qcl_application_Application'"
        );
      }
    }
    catch( qcl_FileNotFoundException $e )
    {
      $this->abort("No valid application.");
      // never gets here
    }

    qcl_application_Application::setInstance( $app );

    return $app;
  }
  
  /**
   * overridden to add the file path and file name to the requst parameters
   */
  function getInput()
  {
    $input = parent::getInput();
    $input->params[] = $this->filePaths;
    $input->params[] = $this->fileNames;
    $input->params[] = $this->error;
    return $input;
  }

  /**
   * Echo a HTML reply, ignored if jsonrpc request
   * @param $msg
   * @return void
   */
  public function echoReply ( $msg )
  {
    if( ! $this->rpcRequest )
    {
      echo $msg;
    }
  }

  /**
   * Echo a HTML warning, if json-rpc request, throw error
   * @param $msg
   * @return unknown_type
   */
  public function echoWarning ( $msg )
  {
    if( $this->rpcRequest )
    {
      $this->error = $msg;
      parent::start();
    }
    else
    {
      echo "<span qcl_error='true'>$msg</span>";
      exit;
    }
  }

  /**
   * Echo a HTML warning and exit. throws error if json-rpc request
   * @param $msg
   * @return unknown_type
   */
  public function abort ( $msg )
  {
    if( $this->rpcRequest )
    {
      $this->error = $msg;
      parent::start();
    }
    else
    {
      echo "<span qcl_error='true'>$msg</span>";
      exit;
    }
  }
}
