<?php
require_once "qcl/data/controller/Controller.php";

class qcl_test_event_Transport
  extends qcl_data_controller_Controller
{
  function test_testServerEvents()
  {
    $this->info("Testing event transport");
    $this->fireServerDataEvent("fooEvent", "Foo!");
    $this->fireServerEvent("barEvent" );
    return true;
  }

  function test_testServerMessages()
  {
    $this->info("Checking message transport");
    $this->dispatchServerMessage("fooMessage", "Foo!");
    $this->broadcastServerMessage("barMessage", "Bar!");
    return true;
  }
}
