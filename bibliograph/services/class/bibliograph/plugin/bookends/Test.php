<?php

qcl_import("bibliograph_controller");
qcl_import("bibliograph_plugins_bookends_Model");

/**
 * Service class containing test methods
 */
class class_bibliograph_plugins_bookends_Test
  extends bibliograph_controller
{
  function method_search( $params ) 
  {
    list ( $datasource, $field, $value ) = $params;
    
    $datasourceModel =& $this->getDatasourceModel($datasource);  
    $bookendsModel   =& new bibliograph_plugins_bookends_Model( $this, $datasourceModel );
    
    $value= utf8_encode($value) ;
    
    
    $bookendsModel->findBy( $field, $value );
    
    $this->info($bookendsModel->request->data);
    
    
    //$bookendsModel->findByAuthor("boulanger");
    if ( $bookendsModel->foundNothing() )
    {
      return "Nothing found";  
    }
    return $bookendsModel->getResult();
  }
  
//
  // TEST METHODS
  //
  
  function method_testExchangeFormat ($params)
  {
    $bibliograph_ds = $params[0]; 
    $bookends_ds    = $params[1];
    $bibliograph_id = $params[2];
            
    $bib_ds_model  =& $this->getDatasourceModel($bibliograph_ds);
    $bib_model     =& $bib_ds_model->getRecordModel();
    
    $be_ds_model   =& $this->getDatasourceModel($bookends_ds);
    $be_model      =& $be_ds_model->getRecordModel();

    /*
     * export
     */
    $bib_model->findById($bibliograph_id);
    $str = $be_model->toExchangeFormat($bib_model->getRecord());
    $this->info($str);

    /*
     * import
     */
    $record = $be_model->fromExchangeFormat($str);
    $this->info($record);
    
  }  
  
  
  
  function method_testDownload($params)
  {
    $datasource = $params[0]; 
    $citekey    = $params[1];
            
    $dsModel       =& $this->getDatasourceModel($datasource);
    $bookendsModel =& $dsModel->getRecordModel(); 
    
    $bookendsModel->findByCitekey($citekey);
      
    if ( $bookendsModel->foundNothing() )
    {
      $result = $bookendsModel->getError() ;
    }
    else
    {
      $result = $bookendsModel->getResult(); 
    }
    $this->info($result); 
    return $result;
    
  }
  
  
  function method_testUpload($params)
  {
    $datasource = $params[0]; 
    
    $dsModel       =& $this->getDatasourceModel($datasource);
    $bookendsModel =& $dsModel->getRecordModel(); 
    
    $bookendsModel->create("Tester2007");
    
    $bookendsModel->set( array(
      'reftype' => "book", 
      'author'  => "Tester, Tom",
      'title'   => "This will not be the last test",
      'year'    => "2007",
      'bibliograph-id' => "0123456"
    ) ); 
    $bookendsModel->update();   
     
  }
  
  function method_testUpdate($params)
  {
    $datasource = $params[0]; 
    $citekey    = $params[1];
    $reftype    = either($params[2],"article");
    
    $dsModel       =& $this->getDatasourceModel($datasource);
    $bookendsModel =& $dsModel->getRecordModel(); 
    
    /*
     * find record(s)
     */
    $bookendsModel->findByCitekey($citekey);
    
    if ( $bookendsModel->foundNothing() )
    {
      if ( $bookendsModel->foundNoMatches() )
      {
        $this->info("Record does not exist. Creating it...");
        $bookends_id = $bookendsModel->create($reftype, $citekey);
        if ( ! $bookends_id )
        {
          throw new LogicException($bookendsModel->getError() );
        }
      }
      else
      {
        throw new LogicException("Cannot update citekey $citekey:" . $bookendsModel->getError() );
      }      
    }
    else
    {
      /*
       * if we have more than one, use the last
       */
      $result = $bookendsModel->getResult();
      $count  = count($result);
      if ( count($result) > 1 )
      {
        $bookendsModel->setRecord($result[$count-1]);    
      } 
      
      $bookends_id = $bookendsModel->getId();
      $this->info( "Record #$bookends_id exists." );
    }
    
    /*
     * show current record
     */
    $this->info("Current record:");
    $this->info($bookendsModel->getRecord());
    
    /*
     * update properties
     */   
    $properties = array_diff(
      $bookendsModel->getProperties(),
      array("id","reftype","citekey")
    );
    foreach ( $properties as $prop )
    {
      $bookendsModel->setProperty($prop, $prop . "-" . $_SESSION['counter']++ );
    }
    
    
    /*
     * show what is going to be updated
     */
    $this->info("Going to update with the following values:");
    $updateData = $bookendsModel->getRecord(); 
    $this->info($updateData);

    /*
     * update
     */
    $bookendsModel->update();
    
    /*
     * check result
     */
    $bookendsModel->findById($bookends_id);
    $this->info("Record #$bookends_id in Bookends database:");
    $currentData = $bookendsModel->getRecord();
    $this->info($currentData);
    
    $failedUpdate = array();
    foreach($updateData as $key => $value )
    {
      $val2 = $currentData[$key];
      if ( isset($currentData[$key]) and $value != $val2 )
      {
        /*
        $str="\n$value\n$val2\n";
        for($i=0;$i<strlen($value);$i++)
        {
          $str .= ( $value{$i} == $val2{$i} ) ? " " : "^";          
        }
        $this->info($str);
        //*/
        $failedUpdate[] = $key;
        
      }
    }
    
    if ( count($failedUpdate) )
    {
      $this->info("Update was not successful. The following properties were not updated: " . implode(",",$failedUpdate) . ".");
    }
    else
    {
      $this->info("Update successful.");
    }
    
    return $this->response();
  }
  
  function method_testWhereQuery($params)
  {
    list( $datasource, $where) = $params;
    
    $dsModel       =& $this->getDatasourceModel($datasource);
    $bookendsModel =& $dsModel->getRecordModel(); 
    
    $bookendsModel->findWhere($where);
    if ( $bookendsModel->foundNothing() )
    {
      $result = $bookendsModel->getError() ;
    }
    else
    {
      $result = $bookendsModel->getResult(); 
    }
    $this->info($result); 
    return $result;            
  }  
  

  
}

?>