<?php

namespace app\modules\bibutils;

class Module extends \app\modules\converters\Module
{
  /**
   * The version of the module
   * @var string
   */
  protected $version = "0.0.5";

  /**
   * Defines the converter classes to install
   * @var array
   */
  protected $install_classes = [
    'import' =>['Bibtex','Biblatex'],
    'export' =>['Bibtex']
  ];
}
