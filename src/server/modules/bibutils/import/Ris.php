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
 * Imports RIS-formatted data
 * @see https://en.wikipedia.org/wiki/RIS_(file_format)
 */
class Ris extends AbstractParser
{

  /**
   * @inheritdoc
   */
  public $id = "ris";

  /**
   * @inheritdoc
   */
  public $name = "RIS (UTF-8)";

  /**
   * @inheritdoc
   */
  public $type = "bibutils";

  /**
   * @inheritdoc
   */
  public $extension = "ris";

  /**
   * @inheritdoc
   */
  public $description = "Bibliographic data exchange format by Research Information Systems";

  /**
   * @inheritdoc
   */
  public function parse( string $data ) : array
  {
    try {
      $data = (new Executable("ris2xml", BIBUTILS_PATH))->call("-u -nt", $data);
      Yii::debug($data, Module::CATEGORY, __METHOD__);
      $data = (new Executable("xml2bib", BIBUTILS_PATH ))->call("-sd -nl -nb", $data);
      Yii::debug($data, Module::CATEGORY, __METHOD__);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    $references = (new BibtexUtf8())->parse($data);
    return $references;
  }
}