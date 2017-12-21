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

namespace app\controllers;

use app\controllers\AppController;

use app\models\User;
use app\models\Role;
use app\models\Permission;
use app\models\Group;


/*
 * the message lifetime in seconds
 */
if ( ! defined("QCL_EVENT_MESSAGE_LIFETIME") )
{
  define( "QCL_EVENT_MESSAGE_LIFETIME" , 60 );
}

/*
 * The interval in milliseconds in which the client polls for messages and events
 */
if ( ! defined("QCL_EVENT_MESSAGE_POLLING_INTERVAL") )
{
  define( "QCL_EVENT_MESSAGE_POLLING_INTERVAL" , 10000 );
}

/*
 * The delay that is added per session, in milliseconds
 */
if ( ! defined("QCL_EVENT_MESSAGE_POLLING_DELAYPERSESSION") )
{
  define( "QCL_EVENT_MESSAGE_POLLING_DELAYPERSESSION" , 100 );
}


class MessageController extends AppController {

  /**
   * Service to collect events and messages waiting for a particular connected session.
   * Returns the number of milliseconds after which to poll again.
   * @return int
   */
  public function getMessages()
  {
    // cleanup stale sessions
    $this->cleanup();

    // determine the polling frequency based on the number of connected users
    $sessionModel = $this->getAccessController()->getSessionModel();
    $numberOfSessions = $sessionModel->countRecords();
    $pollingFrequencyInMs =
      QCL_EVENT_MESSAGE_POLLING_INTERVAL +
      (QCL_EVENT_MESSAGE_POLLING_DELAYPERSESSION*($numberOfSessions-1));

    return $pollingFrequencyInMs;
  }

}