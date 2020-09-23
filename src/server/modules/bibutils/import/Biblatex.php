<?php

/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\modules\bibutils\import;

use app\modules\bibutils\Module;
use app\modules\converters\import\AbstractParser;
use app\modules\converters\import\BibtexUtf8;
use lib\exceptions\UserErrorException;

/**
 * Parser for UTF-8 encoded BibTeX files
 */
class Biblatex extends AbstractParser
{

  /**
   * @inheritdoc
   */
  public $id = "biblatex";

  /**
   * @inheritdoc
   */
  public $name = "Biblatex/Biber (UTF-8)";

  /**
   * @inheritdoc
   */
  public $type = "bibutils";

  /**
   * @inheritdoc
   */
  public $extension = "bbl,bib";

  /**
   * @inheritdoc
   */
  public $description = "Biblatex/Biber (UTF-8), see http://texdoc.net/texmf-dist/doc/latex/biblatex/biblatex.pdf";

  /**
   * @inheritdoc
   */
  public function parse( string $data ) : array
  {
    try {
      $mods = Module::createCmd("biblatex2xml")->call("-u", $data);
      //Yii::debug($mods, Module::CATEGORY, __METHOD__);
      $data = Module::createCmd("xml2bib")->call("-sd -nl", $mods);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    $data = str_replace("\nand ", "; ", $data);
    //Yii::debug($bibtex, Module::CATEGORY, __METHOD__);
    $references = (new BibtexUtf8())->parse($data);
    return $references;
  }
}
