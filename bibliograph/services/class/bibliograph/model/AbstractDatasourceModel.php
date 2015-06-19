<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_datasource_DbModel" );

/**
 * model for bibliograph datasources based on an sql database
 */
abstract class bibliograph_model_AbstractDatasourceModel
  extends qcl_data_datasource_DbModel
{

  /**
   * The properties that can be exported to the client
   */
  protected $clientProperties = array(
    "namedId",
    "title",
    "tableModelType", // @todo rename to itemModelType?
    "tableModelService", // @todo rename?
    "transactionId"
  );
  //@todo add containerModelType, containerModelService?? folder???

  /**
   * Method implemented by subclasses which returns the name of the
   * model type that should be displayed in a table.
   * @return string
   */
  abstract public function getTableModelType();
  
  /**
   * Dummy function to make tableModelType a property
   */
  public function setTableModelType(){}
  
  /**
   * Returns the name of the service that provides table data
   * @return string
   */
  public function getTableModelService()
  {
    return $this->getServiceNameForType( $this->getTableModelType() );
  }
  
  /**
   * Dummy function to make tableModelService a "real" property
   */
  public function setTableModelService(){}
  
  
  /**
   * Dummy function to make transactionId a "real" property,
   * even though you cannot set it
   */
  public function setTransactionId(){}

  /**
   * Finds all models with the given schema. Overrides parent method.
   * @override
   * @return qcl_data_db_Query
   */
  public function findAll()
  {
    return $this->findWhere(array(
      "schema" => $this->getSchemaName()
    ));
  }
}
