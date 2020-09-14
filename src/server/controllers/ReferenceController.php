<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2018 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use lib\controllers\IItemController;
use lib\controllers\ITableController;
use app\models\Datasource;

class ReferenceController
  extends AppController
  implements ITableController, IItemController
{
  use traits\FolderDataTrait;
  use traits\TableControllerTrait;
  use traits\ItemControllerTrait;
  use traits\QueryActionsTrait;
  use traits\TableCommandActionsTrait;
  use traits\MetaActionsTrait;

  /**
   * The main model type of this controller
   */
  static $modelType = "reference";

  /**
   * Returns the name of the folder model class
   *
   * @param string $datasource
   * @return string
   * @todo Rename to getFolderModelClass()
   */
  static function getFolderModel($datasource)
  {
    return Datasource:: in($datasource, "folder");
  }
}

