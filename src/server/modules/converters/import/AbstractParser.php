<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 18.04.18
 * Time: 09:04
 */

namespace app\modules\converters\import;
use app\models\Reference;
use lib\exceptions\UserErrorException;
use Yii;
use yii\base\BaseObject;

/**
 * Class AbstractImporter
 * @property string $id
 * @property string $name
 * @property string $type
 * @property string $extension
 * @package modules\bibutils\import
 */
abstract class AbstractParser extends BaseObject
{

  /**
   * The id of the importer
   * @var string
   */
  public $id;

  /**
   * The descriptive name of the importer
   * @var string
   */
  public $name;

  /**
   * The type of the format
   * @var string
   */
  public $type;

  /**
   * The file extension(s) of the format. If more than one, separate by comma
   * @var string
   */
  public $extension;

  /**
   * A longer description of the importer. Optional.
   * @var string
   */
  public $description;

  /**
   * Check that the given data isutf-8 encoded, if not, throw exception
   * @param $data
   * @throws UserErrorException
   */
  protected function enforceUtf8($data)
  {
    if (!preg_match('!!u', $data))
    {
      throw new UserErrorException(Yii::t('app',"You must convert file to UTF-8 before importing."));
    }
  }

  /**
   * Parses the given data.
   * @todo support streams, to allow efficient handling of very large files
   * @param string $data
   * @return Reference[] of records to be imported
   */
  abstract public function parse(string $data) : array;
}