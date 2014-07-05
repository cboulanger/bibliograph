<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_data_model_db_ActiveRecord" );

/**
 * Singleton active record that holds a transaction id for each model that
 * is increased whenever a transaction is successful. This allows to determine
 * on the clients if they are up-to-date or if they have to re-sync
 * their model data. Records are identified by the class name of the model.
 */
class qcl_data_model_db_TransactionModel
  extends qcl_data_model_db_ActiveRecord
{
  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  protected $tableName = "data_Transaction";

  private $properties = array(
    'datasource'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(50)"
    ),
    'class'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    ),
    'transactionId'  => array(
      'check'     => "integer",
      'sqltype'   => "int(11) default 0",
      'nullable'  => true
    )
  );

  private $indexes = array(
    "datasource_class_index" => array(
      "type"        => "unique",
      "properties"  => array("datasource","class")
    )
  );

  //-------------------------------------------------------------
  // init
  //-------------------------------------------------------------

  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addIndexes( $this->indexes );

    // use the datasource model of the access controller
    $this->setDatasourceModel($this->getApplication()->getAccessController()->getDatasourceModel());
  }

  /**
   * Returns singleton instance of this class
   * @return qcl_data_model_db_TransactionModel
   */
  static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  /**
   * Overridden to skip transaction for this model
   */
  public function getTransactionId()
  {
    return 0;
  }

  /**
   * Overridden to skip transaction for this model
   */
  public function incrementTransactionId(){}

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Getter for the transaction id for a model
   * @param qcl_data_model_AbstractActiveRecord $model
   * @return int The transaction id
   */
  public function getTransactionIdFor( qcl_data_model_AbstractActiveRecord $model )
  {
    $class      = $model->className();
    $datasource = $model->datasourceModel() ? $model->datasourceModel()->namedId() : null;

    $data = $datasource ?
      array('class'=> $class,'datasource' => $datasource):
      array('class'=> $class);

    try
    {
      $this->loadWhere( $data );
    }
    catch ( qcl_data_model_RecordNotFoundException $e)
    {
      $where['transactionId']=0;
      $this->create( $data );
    }

    return (int) $this->_get("transactionId");
  }

  /**
   * Setter for the id of the parent node.
   * @param qcl_data_model_AbstractActiveRecord $model
   * @return int The new transaction id
   */
  public function incrementTransactionIdFor( qcl_data_model_AbstractActiveRecord $model )
  {
    $transactionId = $this->getTransactionIdFor( $model )+1;
    // for some reason, this doesn't work using the model API, we need to directly set the value using update()
    $this->getQueryBehavior()->update(array("transactionId"=>$transactionId),$this->id());
    return $transactionId;
  }

  /**
   * Resets the transaction id
   * @param qcl_data_model_AbstractActiveRecord $model
   * @return void
   */
  public function resetTransactionIdFor( qcl_data_model_AbstractActiveRecord $model )
  {
    $this->getTransactionIdFor( $model );
    $this->_set("transactionId", 0);
    $this->save();
  }
}
?>