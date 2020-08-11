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

namespace app\modules\bibutils\export;
use app\modules\converters\export\AbstractExporter;
use app\modules\converters\export\BibliographBibtex;
use app\models\Reference;
use lib\exceptions\UserErrorException;
use lib\util\Executable;

/**
 * Exports in a format that can be imported by Endnote
 * @see https://en.wikipedia.org/wiki/EndNote#Tags_and_fields
 */
class Endnote extends AbstractExporter
{

  /**
   * @inheritdoc
   */
  public $id = "endnote";

  /**
   * @inheritdoc
   */
  public $preferBatch = true;

  /**
   * @inheritdoc
   */
  public $name = "Endnote";

  /**
   * @inheritdoc
   */
  public $type = "export";

  /**
   * @inheritdoc
   */
  public $mimeType = "application/x-endnote";

  /**
   * @inheritdoc
   */
  public $extension = "enw";

  /**
   * @inheritdoc
   */
  public $description = "Tagged bibliographic data exchange format used by the Endnote Reference Manager";

  /**
   * @inheritdoc
   */
  public function exportOne( Reference $reference )
  {
    return $this->export([$reference]);
  }

  /**
   * @inheritdoc
   */
  public function export(array $references)
  {
    $bibliographBibtex = (new BibliographBibtex())->export($references);
    try {
      $mods = Module::createCmd("bib2xml")->call("-u", $bibliographBibtex);
      //Yii::debug($mods, Module::CATEGORY, __METHOD__);
      $end = Module::createCmd("xml2end")->call("", $mods);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    return $end;
  }
}
