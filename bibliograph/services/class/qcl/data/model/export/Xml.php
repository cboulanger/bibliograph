<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2007-2010 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_data_xml_SimpleXMLElement" );
qcl_import( "qcl_data_model_AbstractExporter" );

class qcl_data_model_export_Xml
  extends qcl_data_model_AbstractExporter
{
  public $metaDataProperties = array( "id","namedId" );

  /**
   * Exports model data to an xml file
   *
   * @param qcl_data_model_AbstractActiveRecord $model Model to import from
   * @return string xml The result xml data
   */
  function export( qcl_data_model_AbstractActiveRecord $model)
  {
    $xmlDoc           = qcl_data_xml_SimpleXMLElement::createDocument();
    $modelNode        = $xmlDoc->addChild("model");
    $dataNode         = $modelNode->addChild("data");
    $linksNode        = $modelNode->addChild("links");
    $relationBehavior = $model->getRelationBehavior();
    $propertyBehavior = $model->getPropertyBehavior();

    /*
     * metadata
     */
    $modelNode->addAttribute("name",$model->className());

    /*
     * list of properties minus those which should be
     * skipped
     */
    $propList = $model->getPropertyBehavior()->exportableProperties();

    $this->log( sprintf(
      "Exporting properties '%s'", implode(",",$propList)
    ), QCL_LOG_MODEL );

    /*
     * record data, sorted by named id if supported
     */
    if( $model instanceof qcl_data_model_AbstractNamedActiveRecord )
    {
      $model->findAllOrderBy("namedId");
    }
    else
    {
      $model->findAll();
    }

    while( $model->loadNext() )
    {
      $recordNode = $dataNode->addChild("record");

      /*
       * dump each column value
       */
      foreach ($propList as $propName )
      {
        /*
         * column data; skip empty columns
         */
        $columnData = $model->get( $propName );

        /*
         * no need to export init values
         */
        if ( $columnData === $propertyBehavior->getInitValue( $propName ) )
        {
          continue;
        }

        if ( $columnData === null )
        {
          continue;
        }
        elseif ( $columnData === false )
        {
          $columnData = 0;
        }

        /*
         * encode data for use in xml
         */
        $data = xml_entity_encode($columnData);

        /*
         * if property is part of metadata, use attribute
         */
        if ( in_array( $propName, $this->metaDataProperties ) )
        {
          $recordNode->addAttribute($propName,$data);
        }

        /*
         * otherwise, create property data node
         */
        else
        {
          $propDataNode = $recordNode->addChild($propName);
          $propDataNode->setCDATA($data);
        }

      }
    }

    /*
     * model associations/links
     */
    foreach( $relationBehavior->relations() as $relation )
    {
      $relNode = $linksNode->addChild("relation");
      $relNode->addAttribute( "name", $relation );
      if( $model instanceof qcl_data_model_AbstractNamedActiveRecord )
      {
        $model->findAllOrderBy("namedId");
      }
      else
      {
        $model->findAll();
      }
      while( $model->loadNext() )
      {
        $targetModel = $relationBehavior->getTargetModel( $relation );
        $ids = $targetModel->getRelationBehavior()->linkedModelIds( $model );

        if ( count($ids) )
        {
          /*
           * source model
           */
          $node = $relNode->addChild("link" );
          if (  $model instanceof qcl_data_model_AbstractNamedActiveRecord )
          {
            $node->addAttribute("namedId", $model->namedId() );
          }
          else
          {
            $node->addAttribute("id", $model->id() );
          }

          /*
           * target model
           */
          if (  $targetModel instanceof qcl_data_model_AbstractNamedActiveRecord )
          {
            $ids = $targetModel->getQueryBehavior()->fetchValues(
              "namedId",
              new qcl_data_db_Query( array(
                'where' => "id IN (" . implode(",", $ids) . ")"
              ) )
            );
          }
          $node->setCDATA( implode(",", $ids) );
        }
      }
    }

    /*
     * return formatted xml
     */
    return $xmlDoc->asXML(null,true);
  }
}
?>