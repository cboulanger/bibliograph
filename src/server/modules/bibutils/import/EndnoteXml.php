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
 * Imports from Endnote tagged format
 * @see https://en.wikipedia.org/wiki/EndNote#Tags_and_fields
 */
class EndnoteXml extends AbstractParser
{

  /**
   * @inheritdoc
   */
  public $id = "endnotexml";

  /**
   * @inheritdoc
   */
  public $name = "Endnote xml format";

  /**
   * @inheritdoc
   */
  public $type = "bibutils";

  /**
   * @inheritdoc
   */
  public $extension = "enx,xml";

  /**
   * @inheritdoc
   */
  public $description = "XML-based data exchange format used by the Endnote Reference Manager";

  /**
   * @inheritdoc
   */
  public function parse( string $data ) : array
  {
    try {
      $mods = (new Executable("endx2xml", BIBUTILS_PATH))->call("-u", $data);
      //Yii::debug($mods, Module::CATEGORY, __METHOD__);
      $data = (new Executable("xml2bib", BIBUTILS_PATH ))->call("-sd -nl", $mods);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    $references = (new BibtexUtf8())->parse($data);
    return $references;
  }
}