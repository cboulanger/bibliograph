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

namespace app\modules\z3950\models;

use lib\models\BaseModel;

class Result extends BaseModel
{

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['firstRow', 'lastRow', 'firstRecordId', 'lastRecordId','SearchId'], 'integer'],
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * Public to avoid magic property access
   * @return \yii\db\ActiveQuery
   */
  public function getSearch()
  {
    return $this->hasOne(Search::class, [ 'id' => 'SearchId' ] );
  }
}
