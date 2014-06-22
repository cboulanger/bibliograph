<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2007-2010 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

require_once dirname(__DIR__)."/bootstrap.php"; 
qcl_import( "qcl_test_TestRunner" );
qcl_import( "qcl_application_Application" );



class qcl_test_event_Event
  extends qcl_test_TestRunner
{

  function test_testEvents()
  {
    $this->info("Testing server-side event system");
    $this->info("My id: #" . $this->objectId() );

    $object1 = new qcl_core_Object;
    $this->info("Adding Listener for 'fooEvent' to object1 -> #" . $object1->objectId() );
    $object1->addListener( "fooEvent", $this, "handleFooEvent");

    $object2 = new qcl_core_Object;
    $this->info("Adding Listener for 'barEvent' to object2 -> #" . $object2->objectId() );
    $object2->addListener( "barEvent", $this, "handleBarEvent");

    $this->info("Firing 'fooEvent' on object1" );
    $object1->fireDataEvent("fooEvent", "Foo!");
    $this->info("Firing 'barEvent' on object1" );
    $object1->fireDataEvent("barEvent", "Bar!");

    $this->info("Firing 'fooEvent' on object2" );
    $object2->fireDataEvent("fooEvent", "Foo!");
    $this->info("Firing 'barEvent' on object2" );
    $object2->fireDataEvent("barEvent", "Bar!");

    return true;
  }

  function handleFooEvent( $event )
  {
    $myId = $this->objectId();
    $targetId = $event->getTargetId();
    $data = $event->getData();
    $this->info("#$myId:  'handleFooEvent' called from #$targetId with '$data' ");
  }

  function handleBarEvent( $event )
  {
    $myId = $this->objectId();
    $targetId = $event->getTargetId();
    $data = $event->getData();
    $this->info("#$myId: 'handleBarEvent' called from #$targetId with '$data' ");
  }

  function test_testMessages()
  {
    $this->info("Testing server-side message system");
    $this->info("My id: #" . $this->objectId() );

    $object1 = new qcl_core_Object;
    $this->info("object1 -> #" . $object1->objectId() );

    $object2 = new qcl_core_Object;
    $this->info("object2 -> #" . $object2->objectId() );

    $this->addSubscriber("barMessage", "handleBarMessage");
    $this->addSubscriber("fooMessage", "handleFooMessage");

    $object1->dispatchMessage("fooMessage", "Foo!");
    $object2->dispatchMessage("barMessage", "Bar!");
  }

  function handleFooMessage( $message )
  {
    $myId = $this->objectId();
    $senderId = $message->getSenderId();
    $data = $message->getData();
    $this->info("#$myId:  'handleFooMessage' called by #$senderId with '$data'.");
  }

  function handleBarMessage( $message )
  {
    $myId = $this->objectId();
    $senderId = $message->getSenderId();
    $data = $message->getData();
    $this->info("#$myId: 'handleBarMessage' called from #$senderId with '$data'.");
  }
}

qcl_test_event_Event::getInstance()->run();
