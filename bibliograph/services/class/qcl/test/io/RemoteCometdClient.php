<?php

require_once "qcl/data/controller/Controller.php";
require_once "qcl/io/remote/cometd/Client.php";

class qcl_test_io_RemoteCometdClient
  extends qcl_data_controller_Controller
{

  function test_sendMessage( $user, $message )
  {
    try
    {
      $cometd = new qcl_io_remote_cometd_Client("http://localhost:8080/cometd");

      $cometd->publish("/chat/demo", array(
        'user' => $user,
        'chat' => $message
      ));
      return $message;
    }
    catch( Exception $e)
    {
     $this->raiseError($e);
    }
  }
}