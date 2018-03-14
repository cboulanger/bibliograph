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
use app\models\Reference;
use BadMethodCallException;

/**
 * Z3950 record model
 * @property integer $SearchId
 */
class Record extends Reference
{

  static function tableName()
  {
    return '{{%data_Record}}';
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
