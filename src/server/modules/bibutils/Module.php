<?php

namespace app\modules\bibutils;

/**
 * The path to the bibutils executables. If set to empty,
 * the bibutils executables must be on the PATH
 */
defined('BIBUTILS_PATH') or define('BIBUTILS_PATH','/usr/local/bin');

class Module extends \app\modules\converters\Module
{
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
}
