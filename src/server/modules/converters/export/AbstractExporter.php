<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 28.04.18
 * Time: 15:10
 */

namespace app\modules\converters\export;

use app\models\Reference;
use Yii;
use yii\base\BaseObject;


/**
 * Abstract superclass from which all exporters must be extended. The exporter must declare whether
 * they are optimized for translating many references at once (exporter->preferBatch=true), what their
 * mime-type is (->mimeType) and
 * It must implement the methods `exportOne()` and `export()`
 *
 * @package app\modules\converters\export
 * @property string $id
 *    The unique is of the exporter
 * @property string §name
 *    A descriptive name
 * @property string $type
 *    The type of the exporter: "style" or "export"
 * @property bool $preferBatch
 *    If true, this exporter prefers to batch-transform lots of references in one go. This is usually the case
 *    if external programs or services are used to transform the data and each call to them has significant overhead.
 *    If false, the exporter allows to translate individual references without overhead. This allows streaming the
 *    result to the client and is less memory-intensive.
 * @property string $mimeType
 *    The mimetype of the documtent to be downloaded.
 * @property string $extension The name of the extension of the file to be downloaded
 */
abstract class AbstractExporter extends BaseObject
{

  const CATEGORY = "plugin.converters";

  /**
   * The id of the exporter
   * @var string
   */
  public $id;

  /**
   * The descriptive name of the exporter
   * @var string
   */
  public $name;

  /**
   * The type of the export format
   * @var string
   */
  public $type;

  /**
   * A longer description of the exporter. Optional.
   * @var string
   */
  public $description;

  /**
   * Iternal state of preferBatch attribute
   * @var bool
   */
  public $preferBatch = false;

  /**
   * Internal state of the mimeType attribute
   * @var string
   */
  public $mimeType = 'application/octet-stream';

  /**
   * Internal state of the extension attribute
   * @var string
   */
  protected $extension;

  /**
   * Getter for extension attribute
   */
  public function getExtension(){
    if( ! $this->extension ){
      throw new \RuntimeException(self::class . " does not define a file extension");
    }
    return $this->extension;
  }

  protected function debugEncoding($string) {
    Yii::debug("Encoding:" . mb_detect_encoding($string), static::CATEGORY);
    Yii::debug($string, static::CATEGORY);
  }

  /**
   * Exports a single reference
   * @param Reference $reference
   * @return string
   */
  abstract function exportOne( Reference $reference);


  /**
   * Exports an array of references
   * @param Reference[] $references
   * @return string
   */
  abstract function export( array $references);

}
