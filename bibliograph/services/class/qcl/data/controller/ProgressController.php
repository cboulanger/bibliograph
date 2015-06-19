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

  /**
   * The number of the current step
   * @var int
   */
  protected $step = 0;

  /**
   * The properties of the progress dialog widget
   * @var array
   */
  protected $dialogProperties = array();

  /**
   * Returns a list of methods to be consecutively called.
   * Abstract method to be implemented by the subclassed
   * @return array
   */
  abstract protected function getStepMethods();

  /**
   * Returns the number of steps to be completed. By default, returns
   * the number of methods provided by getStepMethods()
   * @return int
   */
  protected function getStepCount()
  {
    return count($this->getStepMethods());
  }

  /**
   * getter for step property
   * @return int
   */
  protected function getStep()
  {
    return $this->step;
  }

  /**
   * getter for dialog properties which automatically updates the "progress" property value
   * @return array
   */
  protected function getDialogProperties()
  {
    $dialogProperties = $this->dialogProperties;
    $dialogProperties['progress'] = $this->getProgress();
    return $dialogProperties;
  }

  /**
   * Returns the progress percentage in a rounded number between 0 and 100.
   * @return int
   */
  protected function getProgress()
  {
    return round( ( $this->step / $this->getStepCount() ) *100 );
  }

  /**
   * Setter for message property of the dialog widget
   * @param string $message
   */
  protected function setMessage( $message )
  {
    $this->dialogProperties['message'] = $message;
  }

  /**
   * Adds a new line to the log in the progress dialog widget
   * @param $text
   */
  protected function addLogText ( $text )
  {
    $this->dialogProperties['newLogText'] = $text;
  }

  /**
   * The entry method for starting the progressive task.
   * @param $message The message to display in the progress dialog widget
   * @param mixed|null $data The data to pass to the first method
   * @return qcl_ui_dialog_Progress
   * @throws JsonRpcException
   */
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

  /**
   * The method that is called by the progress dialog widget on the client and
   * which iterates over all the methods of the progressive task.
   * @param $dummy
   * @param $shelfId
   * @param $step
   * @return qcl_ui_dialog_Progress
   */
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

  /**
   * The method called after the progressive task has completed. By default,
   * dispatches a message to hide the progress dialog widget on the client.
   * @return qcl_ui_dialog_Progress
   */
  protected function finish()
  {
    return new qcl_ui_dialog_Progress( array( 'show' => false ), null, null, null );
  }
}