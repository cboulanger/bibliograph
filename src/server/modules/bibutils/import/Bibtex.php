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
class Bibtex extends AbstractParser
{

  /**
   * @inheritdoc
   */
  public $id = "bibtex";

  /**
   * @inheritdoc
   */
  public $name = "BibTex with LaTeX character encoding (ASCII)";

  /**
   * @inheritdoc
   */
  public $type = "bibutils";

  /**
   * @inheritdoc
   */
  public $extension = "bib,bibtex";

  /**
   * @inheritdoc
   */
  public $description = "This importer expects BibTeX data with the original 7-bit LaTeX character encoding";

  /**
   * @inheritdoc
   */
  public function parse( string $data ) : array
  {
    try {
      $mods = (new Executable("bib2xml", BIBUTILS_PATH))->call("-u", $data);
      //Yii::debug($mods, Module::CATEGORY, __METHOD__);
      $data = (new Executable("xml2bib", BIBUTILS_PATH ))->call("-sd -nl", $mods);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    $data = str_replace("\nand ", "; ", $data);
    //Yii::debug($bibtex, Module::CATEGORY, __METHOD__);
    $references = (new BibtexUtf8())->parse($data);
    return $references;
  }
}