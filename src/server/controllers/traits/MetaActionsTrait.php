<?php

namespace app\controllers\traits;

use app\models\Reference;
use lib\exceptions\UserErrorException;
use Yii;

trait MetaActionsTrait {

  abstract function getControlledModel($datasource);

  /**
   * Returns an array of ListItem data on the available reference types
   * @param $datasource
   * @return array
   * @throws UserErrorException
   */
  public function getReferenceTypeListData($datasource)
  {
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $this->getControlledModel($datasource)::getSchema();
    $reftypes = $schema->types();
    $options = array();
    foreach ($reftypes as $reftype) {
      try {
        $options[] = array(
          'value' => $reftype,
          'icon' => null, //"icon/16/actions/document-new.png",
          'label' => Yii::t('app', $schema->getTypeLabel($reftype))
        );
      } catch (\Exception $e) {
        throw new UserErrorException($e->getMessage(), null, $e);
      }
    }
    return $options;
  }

  /**
   * Returns the title label for the reference editor form
   *
   * @param Reference $reference
   * @return void
   */
//  protected function getTitleLabel($reference)
//  {
//    $datasource = $reference::getDatasource();
//    $ids = [$reference->id];
//    $style = "apa"; // @todo
//    return "TODO";
//    //return CitationController :: process( $datasource, $ids, $style );
//  }

  /**
   * Returns data for the reference type select box
   * @param $datasource
   *
   */
  public function actionReferenceTypeList($datasource)
  {
    $modelClass = $this->getControlledModel($datasource);
    /** @var \app\schema\AbstractReferenceSchema $schema */
    $schema = $modelClass::getSchema();
    $result = array();
    foreach ($schema->types() as $type) {
      try {
        $result[] = array(
          'label' => $schema->getTypeLabel($type),
          'value' => $type,
          'icon' => null
        );
      } catch (\Exception $e) {
        throw new UserErrorException($e->getMessage(),null, $e);
      }
    }
    return $result;
  }

  /**
   * Returns data for the store that populates reference type lists
   * @param $datasource
   *
   */
  function actionTypes($datasource)
  {
    return $this->getReferenceTypeListData($datasource);
  }


  /**
   * Returns distinct values for a field, sorted alphatbetically, in a format suitable
   * for a ComboBox widget.
   * @param $datasource
   * @param $field
   */
  public function actionListField($datasource, $field)
  {
    $modelClass = $this->getControlledModel($datasource);
    $values = $modelClass::find()
      ->select($field)
      ->distinct()
      ->orderBy($field)
      ->column();
    $result = [];
    foreach ($values as $value) {
      $value = trim($value);
      if ($value) {
        $result[] = array(
          'label' => $value,
          'value' => $value,
          'icon' => null
        );
      }
    }
    return $result;
  }
}
