<?php

namespace app\controllers\traits;

use Yii;

trait CrudTrait
{
  /**
   * Returns data on service and model type that provides data for the
   * given datasource.
   *
   * @param string $datasource
   * @param $modelType
   * @throws JsonRpcException
   * @return array
   */
  public function actionModelinfo($datasource, $modelType)
  {
    not_implemented();
    $datasourceModel = $this->getDatasourceModel( $datasource );
    $serviceName = $datasourceModel->getServiceNameForType( $modelType );
    if (! $serviceName) {
      throw new JsonRpcException( sprintf(
      "No service defined for datasource class %s, model type %s",
       $datasourceModel->className(), $modelType
      ) );
    }
    return array(
    'serviceName' => $serviceName
    );
  }


  /**
   * Creates a record in the given model.
   *
   * @param string $datasource
   * @param string $modelType
   * @param object $data
   * @return int Id of the new model record
   */
  public function actionCreate($datasource, $modelType, $data)
  {
    not_implemented();
    /*
   * check access to model and get model
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * specifically check authorization to create a record
     */
    $properties = array_keys( get_object_vars( $data ) );
    $this->checkAccess( QCL_ACCESS_CREATE, $datasource, $modelType, $properties );

    /*
   * create it
     */
    return $model->create( $data );
  }

  /**
   * Updates a record in the given model.
   *
   * @param string $datasource
   * @param string $modelType
   * @param int|string $id Numeric id or string named id, depending on model
   * @param object $data
   * @return string "OK" if successful
   */
  public function actionUpdate($datasource, $modelType, $id, $data)
  {
    not_implemented();
    /*
   * check access to model and get model
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * specifically check authorization to create a record
     */
    $properties = array_keys( get_object_vars( $data ) );
    $this->checkAccess( QCL_ACCESS_WRITE, $datasource, $modelType, $properties );

    /*
   * load and update it. this will throw an error if it doesn't exist
   * or if access to this model is not allowed
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    $model->set( $data );
    $model->save();
    return "OK";
  }

  /**
   * Deletes a record in the given model.
   *
   * @param string $datasource
   * @param string $modelType
   * @param int|string $id Numeric id or string named id, depending on model
   * @return string "OK" if successful
   */
  public function actionDelete($datasource, $modelType, $id)
  {
    not_implemented();
    /*
   * check access to model and get model
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * specifically check authorization to create a record
     */
    $this->checkAccess( QCL_ACCESS_DELETE, $datasource, $modelType, "*" );

    /*
   * load and update it. this will throw an error if it doesn't exist
   * or if access is not allowed
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    $model->delete();
    return "OK";
  }

  /**
   * Returns the result of a "fetchAll" operation on the given model of the
   * given datasource.
   *
   * @param string $datasource
   * @param string $modelType
   * @param object $query Must be an qcl_data_db_Query - like object
   * @throws InvalidArgumentException
   * @return array
   */
  public function actionFetch($datasource, $modelType, $query)
  {
    not_implemented();
    /*
   * check arguments
     */
    if (! $query instanceof qcl_data_db_Query) {
      if (is_object( $query )) {
        $query = new qcl_data_db_Query( object2array( $query )  );
      } else {
        throw new InvalidArgumentException("Invalid query data.");
      }
    }

    /*
   * check access to model and get it
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * check read access to properties
     */
    $properties = $query->getProperties();
    $this->checkAccess( QCL_ACCESS_READ, $datasource, $modelType, $properties );

    /*
   * add 'id' property if not already there
     */
    if (! in_array( "id", $properties )) {
      array_unshift( $properties, "id" );
      $query->setProperties( $properties );
    }

    /*
   * check read access to properties in "where" clause
     */
    $where = $query->getWhere();
    if ($where) {
      if (! is_array(  $where ) or ! count( $where )) {
        throw new InvalidArgumentException( "Invalid 'where' data.");
      }
      $whereProps = array_keys( $where );
      $this->checkAccess( QCL_ACCESS_READ, $datasource, $modelType, $whereProps );
    }

    /*
   * allow subclasses to manipulate the query object
     */
    $query = $this->fetchRecordsQueryCallback( $datasource, $modelType, $query );

    /*
   * do the query
     */
    $data = $model->getQueryBehavior()->fetchAll( $query );

    /*
   * if no record acl rules have been set up, return unfiltered data
     */
    if (! $this->hasRecordAcl()) {
      return $data;
    }

    /*
   * otherwise filter rows to which the access in not allowed
     */
    $filteredData = array();
    for ($i=0; $i<count($data); $i++) {
      if ($this->hasRecordAccess( $datasource, $modelType, $data[$i] )) {
        $filteredData[] = $data[$i];
      } else {
        //$this->debug( "Ignoring " . $data[$i]['data']);
      }
    }
    return $filteredData;
  }

  /**
   * Hook for subclasses to do something with the query passed
   * to the fetchRecords service method before the query is
   * executed.
   *
   * @param string $datasource
   * @param string $modelType
   * @param qcl_data_db_Query $query
   * @return qcl_data_db_Query By default, simply pass back the object
   */
  protected function fetchRecordsQueryCallback($datasource, $modelType, qcl_data_db_Query $query)
  {
    return $query;
  }

  /**
   * Returns the values of a property that matches a where condition
   * @param string $datasource
   * @param string $modelType
   * @param string $property
   * @param object $where
   * @return array
   */
  public function actionFetchValues($datasource, $modelType, $property, $where)
  {
    not_implemented();
    $model = $this->getModel( $datasource, $modelType );
    $model->findWhere( object2array( $where ) );
    $result = array();
    if ($this->hasRecordAcl()) {
      while ($model->loadNext()) {
        if ($this->hasRecordAccess( $datasource, $modelType, $model->data() )) {
          $result[] = $model->get( $property );
        }
      }
      return $result;
    } else {
      return $model->getQueryBehavior()->fetchValues( $property, object2array( $where ) );
    }
  }

  /**
   * Returns the value of a property of a record identified by the id.
   * Throws an error if no access to the property.
   * @param string $datasource
   * @param string $modelType
   * @param string $id
   * @param $property
   * @throws InvalidArgumentException
   * @return mixed
   */
  public function actionGetValue($datasource, $modelType, $id, $property)
  {
    not_implemented();
    /*
   * get model and check whether the id is numeric or a string
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * Check property
     */
    if (! $model->hasProperty( $property )) {
      throw new InvalidArgumentException("Model '$model' has no property '$property' !");
    }

    /*
   * Check property-level access
     */
    $this->checkAccess( QCL_ACCESS_READ, $datasource, $modelType, array( $property ) );

    /*
   * Run query
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    return $model->get($property);
  }

  /**
   * Sets the value of a property of a record identified by the id. Throws
   * an error if no access to that property.
   * @param string $datasource
   * @param string $modelType
   * @param string $id
   * @param $property
   * @param $value
   * @throws InvalidArgumentException
   * @return mixed
   */
  public function method_setValue($datasource, $modelType, $id, $property, $value)
  {
    /*
   * get model and check whether the id is numeric or a string
     */
    $model = $this->getModel( $datasource, $modelType );

    /*
   * Check property
     */
    if (! $model->hasProperty( $property )) {
      throw new InvalidArgumentException("Model '$model' has no property '$property' !");
    }

    /*
   * check property-level access
     */
    $this->checkAccess( QCL_ACCESS_WRITE, $datasource, $modelType, array( $property ) );

    /*
   * Run query
     */
    $model->load( $id );
    $this->checkRecordAccess( $datasource, $modelType, $model->data() );
    $model->set($property, $value);
    $model->save();
  }
}