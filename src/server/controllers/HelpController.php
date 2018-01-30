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

use Yii;
use app\controllers\AppController;

class HelpController extends AppController
{
 /**
   * Returns the html for the search help text
   * @todo reimplement
   * @param string $datasource
   * @return string
   */
  public function actionSearch( $datasource )
  {
    return "Currently not available.";

    $html = "<p>";
    $html .= Yii::t('app', "You can use the search features of this application in two ways.");
    $html .= " ". Yii::t('app', "Either you simply type in keywords like you would do in a google search.");
    $html .= " ". Yii::t('app', "Or you compose complex queries using field names, comparison operators, and boolean connectors (for example: title contains constitution and year=1981).");
    $html .= " ". Yii::t('app', "You can use wildcard characters: '?' for a single character and '*' for any amount of characters.");
    $html .= " ". Yii::t('app', "When using more than one word, the phrase has to be quoted (For example, title startswith \"Recent developments\").");
    $html .= " ". Yii::t('app', "You can click on any of the terms below to insert them into the query field.");
    $html .= "</p>";

    $modelClass = static::getControlledModel( $datasource );
    $schema = $modelClass::getSchema();

    /*
     * field names
     */
    $html .= "<h4>" . Yii::t('app', "Field names") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    $indexes = array();
    $activeUser = $this->getActiveUser();
    foreach( $schema->fields() as $field )
    {
      $data = $schema->getFieldData( $field );

      if( isset( $data['index'] ) )
      {
        if ( isset( $data['public'] ) and $data['public'] === false )
        {
          if ( $activeUser->isAnonymous() ) continue;
        }
        foreach( (array) $data['index'] as $index )
        {
          $indexes[] = Yii::t('app', $index);
        }
      }
    }

    sort( $indexes );
    foreach( array_unique($indexes) as $index )
    {
      /*
       * don't show already translated field names
       */
      $html .= "<span style='$style' value='$index'>$index</span> ";
    }

    /*
     * modifiers
     */
    $html .= "</p><h4>" . Yii::t('app', "Comparison modifiers") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    
    $qcl = bibliograph_schema_CQL::getInstance();
    $modifiers = $qcl->modifiers;

    sort( $modifiers );
    foreach( $modifiers as $modifier )
    {
      $modifier = Yii::t('app', $modifier);
      $html .= "<span style='$style' value='$modifier'>$modifier</span> ";
    }

    /*
     * booleans
     */
    $html .= "</p><h4>" . Yii::t('app', "Boolean operators") . "</h4>";
    $html .= "<p style='line-height:2em'>";

    $style = "border: 1px solid grey; padding:2px";

    $booleans = array( Yii::t('app', "and") );

    sort( $booleans );
    foreach( $booleans as $boolean )
    {
      /*
       * don't show already translated field names
       */
      $html .= "<span style='$style' value='$boolean'>$boolean</span> ";
    }

    $html .= "</p>";

    return $html;
  }
}