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
use lib\util\Executable;
use lib\bibtex\BibtexParser;
use Yii;

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
  public function parse( string $bibtex ) : array
  {
    try {
      $mods = (new Executable("biblatex2xml", BIBUTILS_PATH))->call("-u", $bibtex);
      //Yii::debug($mods, Module::CATEGORY);
      $bibtex = (new Executable("xml2bib", BIBUTILS_PATH ))->call("-sd -nl", $mods);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    $bibtex = str_replace("\nand ", "; ", $bibtex);
    //Yii::debug($bibtex, Module::CATEGORY);
    $references = (new BibtexUtf8())->parse($bibtex);
    return $references;
  }
}