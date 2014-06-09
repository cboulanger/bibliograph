<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */
qcl_import("qcl_data_controller_Controller");
qcl_import("qcl_ui_dialog_Progress");


/**
 * Controller that controls a task that is performed in a series of
 * consecutive steps/requests. Uses a progress bar widget to indicate the
 * progress of the task
 *
 */
abstract class qcl_data_controller_ProgressController
  extends qcl_data_controller_Controller
{

  protected $step = 0;

  protected $dialogProperties = array();

  abstract protected function getStepMethods();

  protected function getStepCount()
  {
    return count($this->getStepMethods());
  }

  protected function getStep()
  {
    return $this->step;
  }

  protected function getDialogProperties()
  {
    $dialogProperties = $this->dialogProperties;
    $dialogProperties['progress'] = $this->getProgress();
    return $dialogProperties;
  }

  protected function getProgress()
  {
    return round( ( $this->step / $this->getStepCount() ) *100 );
  }

  protected function setMessage( $message )
  {
    $this->dialogProperties['message'] = $message;
  }

  protected function addLogText ( $text )
  {
    $this->dialogProperties['newLogText'] = $text;
  }

  public function method_start( $message, $data=null )
  {
    foreach ( $this->getStepMethods() as $method )
    {
      if ( ! method_exists( $this, $method ) )
      {
        throw new JsonRpcException("Cannot start process: step method $method does not exist.");
      }
    }

    $this->setMessage($message);
    $this->step = 0;

    return new qcl_ui_dialog_Progress(
      $this->getDialogProperties(),
      $this->serviceName(),
      "next",
      array( $this->shelve( $data, $this->getStepMethods() ), 1 )
    );
  }

  public function method_next( $dummy, $shelfId, $step )
  {
    $this->step = $step;

    list( $data, $stepMethods ) = $this->unshelve( $shelfId );

    if ( $step > $this->getStepCount() or count ($stepMethods) == 0 )
    {
      return $this->finish($data);
    }

    $nextMethod = array_shift( $stepMethods );

    $data = $this->$nextMethod($data);

    return new qcl_ui_dialog_Progress(
      $this->getDialogProperties(),
      $this->serviceName(),
      "next",
      array( $this->shelve( $data, $stepMethods ), $step+1 )
    );
  }

  protected function finish()
  {
    return new qcl_ui_dialog_Progress( array( 'show' => false ), null, null, null );
  }
}