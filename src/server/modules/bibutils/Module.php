<?php

namespace app\modules\bibutils;

use lib\util\Executable;

/**
 * The path to the bibutils executables. Defaults to the environment variable of
 * the same name. If empty, the bibutils executables must be on the PATH
 */
defined('BIBUTILS_PATH') or define('BIBUTILS_PATH', $_SERVER['BIBUTILS_PATH']);

class Module extends \app\modules\converters\Module
{
  const CATEGORY = "plugin.bibutils";

  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.1.1";

  /**
   * Defines the converter classes to install
   * @var array
   */
  protected $install_classes = [
    'import' =>['Bibtex','Biblatex','Ris','Endnote','EndnoteXml'],
    'export' =>['Bibtex','Ris','Endnote']
  ];

  /**
   * Creates a new Executable object which can run the given command
   * @param $cmd
   * @return Executable
   */
  static function createCmd($cmd) {
    return new Executable($cmd, BIBUTILS_PATH);
  }
}
