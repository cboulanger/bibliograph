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

namespace app\modules\webservices\models;
use app\models\Reference;
use BadMethodCallException;

/**
 * Webservices record model
 * @property integer $SearchId
 */
class Record extends Reference
{

  static function tableName()
  {
    return '{{%data_Record}}';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified','SearchId'], 'safe'],
      [['SearchId'], 'integer'],
      [['abstract', 'annote', 'contents', 'note'], 'string'],
      [['citekey', 'affiliation', 'crossref', 'date', 'doi', 'edition', 'month', 'size', 'type', 'volume'], 'string', 'max' => 50],
      [['reftype', 'issn', 'key', 'language', 'year'], 'string', 'max' => 20],
      [['address', 'author', 'booktitle', 'subtitle', 'editor', 'howpublished', 'institution', 'keywords', 'lccn', 'title', 'url'], 'string', 'max' => 255],
      [['copyright', 'journal', 'location', 'organization', 'publisher', 'school'], 'string', 'max' => 150],
      [['isbn', 'number', 'pages', 'price'], 'string', 'max' => 30],
      [['series'], 'string', 'max' => 200],
      [['translator'], 'string', 'max' => 100],
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * @throws BadMethodCallException
   */
  public function getReferenceFolders()
  {
    throw new BadMethodCallException("Method " . __METHOD__  . " not supported in " . self::class );
  }

  /**
   * @throws BadMethodCallException
   */
  public function getFolders()
  {
    throw new BadMethodCallException("Method " . __METHOD__  . " not supported in " . self::class );
  }
}
