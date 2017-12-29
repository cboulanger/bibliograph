<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2004-2017 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

namespace app\models;
use Yii;
use app\models\Datasource;

/**
 * model for bibliograph datasources based on an sql database
 */
class BibliographicDatasource
  extends Datasource
{

  /**
   * @return string
   */
  public function getTableModelType()
  {
    return 'reference';
  }

  /**
   * Initialize the datasource, registers the models
   */
  public function init()
  {
    parent::init();
    $this->addModel( 'reference', 'app\models\Reference', 'reference');
    $this->addModel( 'folder', 'app\models\Folder', 'folder');
  }
}
