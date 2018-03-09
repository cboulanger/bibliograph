<?php

namespace lib\util;
use Exception;
use yii\base\BaseObject;

/**
 * A wrapper around proc_open()
 * @property string|null $stdIn
 * @property string|null $stdOut
 * @property string|null $stdErr
 * @property int $exitCode
 */
class Executable extends BaseObject
{

  /**
   * @var string
   */
  protected $cmd;

  /**
   * @var string|null
   */
  protected $arguments;

  /**
   * @var string
   */
  protected $stdin;

  /**
   * @var string
   */
  protected $stdout;

  /**
   * @var string
   */
  protected $stderr;

  /**
   * @var int
   */
  protected $exitcode;

  /**
   * @var null|string
   */
  protected $cwd;

  /**
   * @var array
   */
  protected $env;

  /**
   * @var array
   */
  protected $options;

  /**
   * Constructor.
   * @param string $cmd
   *    Name of the command
   * @param string $cwd
   *    Optional working directory
   * @param array $env
   *    Optional array of environment variables
   * @param array $options
   *    Optional additional options for proc_open
   */
  public function __construct($cmd, $cwd = null, $env = null, $options = array())
  {
    $this->cmd = $cmd;
    $this->cwd = $cwd;
    $this->options = $options;
  }

  /**
   * Return the data that was fed into the executable
   * @return string
   */
  public function getStdIn()
  {
    return $this->stdin;
  }

  /**
   * Returns the data that was returned by the executable
   * @return string
   */
  public function getStdOut()
  {
    return $this->stdout;
  }

  /**
   * Returns the error data returned by the executable
   * @return string
   */
  public function getStdErr()
  {
    return $this->stderr;
  }

  /**
   * Returns the exit code returned by the executable
   * @return int
   */
  public function getExitCode()
  {
    return (int)$this->exitcode;
  }

  /**
   * Internal method which does the actual calling of the
   * executable.
   *
   * @param $cmd
   * @param $input
   * @return array
   * @throws Exception
   */
  protected function call_executable($cmd, $input = null)
  {
    $proc = proc_open($cmd, array(
      array("pipe", "r"),
      array("pipe", "w"),
      array("pipe", "w")
    ), $pipes, $this->cwd, $this->env, $this->options);
    if (is_resource($proc)) {
      if ($input) fwrite($pipes[0], $input);
      fclose($pipes[0]);
      $stdout = stream_get_contents($pipes[1]);
      fclose($pipes[1]);
      $stderr = stream_get_contents($pipes[2]);
      fclose($pipes[2]);
      $exitcode = proc_close($proc);
      return array(
        'stdout' => $stdout,
        'stderr' => $stderr,
        'exitcode' => $exitcode
      );
    }
    throw new Exception("Could not create process '$cmd'");
  }

  /**
   * Calls the executable, returning the exit code. The
   * stdout and stderr data can be retrieved using the
   * getStdOut() and getStdErr() methods.
   *
   * @param string $arguments Optional command-line arguements
   * @param string $stdin Optional data fed into the executable
   * @return int Exit code
   * @throws Exception
   */
  public function exec($arguments = "", $stdin = "")
  {
    $this->arguments = $arguments;
    $this->stdin = $stdin;
    $result = $this->call_executable(
      $this->cmd . " " . $arguments, $stdin
    );
    $this->stdout = $result['stdout'];
    $this->stderr = $result['stderr'];
    $this->exitcode = $result['exitcode'];
    return $this->exitcode;
  }

  /**
   * Calls the executable, returning the stdout data. If
   * the exit code is not 0, throw an exeption using the
   * stderr data as message for qcl_util_system_ShellException.
   *
   * @param string $arguments Optional command-line arguements
   * @param string $stdin Optional data fed into the executable
   * @return string Data returned by the executable to stdout.
   * @throws Exception
   */
  public function call($arguments = "", $stdin = "")
  {
    if ($this->exec($arguments, $stdin) == 0) {
      return $this->getStdOut();
    } else {
      throw new Exception($this->getStdErr());
    }
  }
}
