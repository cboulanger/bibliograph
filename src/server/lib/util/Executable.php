<?php

namespace lib\util;

use Exception;
use yii\base\BaseObject;

/**
 * A wrapper around proc_open()
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
   * @var array|null
   */
  protected $env=null;

  /**
   * @var array|null
   */
  protected $options=null;

  /**
   * Constructor.
   * @param string|array $first
   *    If string, name of the command. If array, configuration map
   * @param string|null $cwd
   *    Optional working directory
   * @param array|null $env
   *    Optional array of environment variables
   * @param array|null $options
   *    Optional additional options for proc_open
   */
  public function __construct( $first, string $cwd = null, array $env = null, array $options = null)
  {
    // Yii-style
    if( is_array( $first) ){
      return parent::__construct($first);
    }
    // BC constructor style
    parent::__construct();
    $this->cmd = $first;
    $this->cwd = $cwd;
    $this->env = $env;
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
   * @param string $cmd
   * @param string|null $input
   * @return array
   * @throws Exception
   */
  protected function call_executable(string $cmd, string $input = null)
  {
    $proc = proc_open($cmd, [
      0 => ["pipe", "r"], // STDIN
      1 => ["pipe", "w"], // STOUT
      2 => ["pipe", "w"]  // STDERR
    ], $pipes, $this->cwd, $this->env, $this->options);
    // make STDERR non-blocking
    //stream_set_blocking($pipes[2], 0);
    // handle streams if we have a resource
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
   * @param string $arguments|null Optional command-line arguements
   * @param string $stdin|null Optional data fed into the executable
   * @return int Exit code
   * @throws Exception
   */
  public function exec(string $arguments = null, string $stdin = null)
  {
    $this->arguments = $arguments;
    $this->stdin = $stdin;
    $result = $this->call_executable(
      $this->cmd . " " . $arguments,
      $stdin
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
   * @param string|null $arguments
   *    Optional command-line arguments
   * @param string $stdin
   *    Optional data fed into the executable
   * @return string Data returned by the executable to stdout.
   * @throws Exception
   */
  public function call(string $arguments = null, string $stdin = null)
  {
    if ($this->exec($arguments, $stdin) === 0) {
      return $this->getStdOut();
    }
    throw new Exception($this->getStdErr());
  }
}
