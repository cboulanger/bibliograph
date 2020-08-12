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
use app\modules\bibutils\Module;
use app\modules\converters\export\AbstractExporter;
use app\modules\converters\export\BibliographBibtex;
use app\models\Reference;
use lib\exceptions\UserErrorException;


/**
 * Exports standard ASCII BibTeX
 */
class Bibtex extends AbstractExporter
{

  /**
   * @inheritdoc
   */
  public $id = "bibtex";

  /**
   * @inheritdoc
   */
  public $preferBatch = true;

  /**
   * @inheritdoc
   */
  public $name = "BibTeX (ASCII)";

  /**
   * @inheritdoc
   */
  public $type = "export";

  /**
   * @inheritdoc
   */
  public $mimeType = "application/x-bibtex";

  /**
   * @inheritdoc
   */
  public $extension = "bib";

  /**
   * @inheritdoc
   */
  public $description = "Exports standard 7-bit BibTeX with LaTeX character encoding ";

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
      $bibtex = Module::createCmd("bib2xml")->call("-sd -w", $mods);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    return $bibtex;
  }
}
