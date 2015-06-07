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

qcl_import( "qcl_data_model_AbstractImporter" );
qcl_import( "qcl_io_filesystem_IFile" );
qcl_import( "qcl_data_xml_SimpleXMLElement" );

/**
 * Importer implementation for xml files. Importing linked data works only
 * with instances of qcl_data_model_db_NamedActiveRecord or, when using
 * qcl_data_model_db_ActiveRecord, if
 * 1) the tables do not exist beforehand or have been truncated, and
 * 2) all records are sequentially numbered starting with id 1, so that
 * the id and foreign id values match with the primary key assigned by
 * the backend.
 *
 */
class qcl_data_model_import_Xml
  extends qcl_data_model_AbstractImporter
{

  /**
   * qcl_data_xml_SimpleXMLElement
   * @var
   */
  protected $xmlDoc;

  /**
   * Constructor
   * @param string|qcl_io_filesystem_IFile $xml
   * @throws InvalidArgumentException
   * @return \qcl_data_model_import_Xml
   */
  function __construct( $xml )
  {
    if ( is_string($xml ) )
    {
      $this->xmlDoc = qcl_data_xml_SimpleXMLElement::createFromString( $xml );
    }
    elseif ( $xml instanceof qcl_io_filesystem_IFile )
    {
      if ( ! $xml->exists() )
      {
        throw new InvalidArgumentException( sprintf(
          "File '%s' does not exist: ", $xml->filePath()
        ) );
      }
      $this->xmlDoc = qcl_data_xml_SimpleXMLElement::createFromFile($xml);
    }
    else
    {
      throw new InvalidArgumentException( "Invalid paraemter. Must be xml string or class implementing qcl_io_filesystem_IFile" );
    }
  }

  public function import( qcl_data_model_AbstractActiveRecord $model )
  {
    $xmlDoc           = $this->xmlDoc;
    //$relationBehavior = $model->getRelationBehavior();
    $propertyBehavior = $model->getPropertyBehavior();
    $className        = $model->className();

    /*
     * metadata
     */
    if ( ! $xmlDoc->model )
    {
      throw new JsonRpcException("Cannot import: Missing model node.");
    }

    if ( (string) $xmlDoc->model['name'] != $className )
    {
      throw new JsonRpcException( sprintf(
        "Cannot import: Incorrect or missing model class. Expected '%s', got '%s'.",
        $className, (string) $xmlDoc->model['name']
      ) );
    }

    /*
     * data
     */
    if ( ! $xmlDoc->model->data )
    {
      throw new JsonRpcException("Cannot import: Missing data for '$className'. ");
    }

    /*
     * record data
     */
    foreach ( $xmlDoc->model->data->children() as $recordNode )
    {
      if ( $model instanceof qcl_data_model_AbstractNamedActiveRecord )
      {
        $namedId = (string) $recordNode['namedId'];
        if( ! $namedId )
        {
          throw new JsonRpcException("Cannot import: Missing named id in data for '$className'. ");
        }
        $model->create( $namedId );
      }
      else
      {
        $model->create();
      }

      /*
       * set each property value
       */
      foreach ( $recordNode->children() as $propertyNode )
      {
        $property = $propertyNode->getName();
        $value    = (string) $propertyNode;
        $model->set( $property, $propertyBehavior->typecast( $property, $value ) );
      }
      $model->save();
    }

    /*
     * model associations/links
     */
    foreach( $xmlDoc->model->links->children() as $relNode )
    {
      $relation = (string) $relNode['name'];
      $targetModel = $model->getRelationBehavior()->getTargetModel( $relation );

      foreach ( $relNode->children() as $linkNode )
      {

        /*
         * source model
         */
        if ( $model instanceof qcl_data_model_AbstractNamedActiveRecord )
        {
          $key  = "namedId";
          $type = "string";
        }
        else
        {
          $key = "id";
          $type = "double";
        }
        if ( ! isset( $linkNode[ $key ] ) )
        {
          throw new JsonRpcException( sprintf(
            "Cannot import: Missing '%s' property in link data for '%s', relation '%s'. ",
            $key, $className, $relation
          ) );
        }
        $id = $linkNode[$key];
        settype( $id, $type );
        
        try
        {
          $model->load( $id );
        }
        catch( qcl_data_model_RecordNotFoundException $e )
        {
          $this->warn("Import: Cannot link to non-existent record [$className#$id], omitting...");
          continue;
        }

        /*
         * link target model(s)
         */
        $targetIds = explode(",", (string) $linkNode );
        foreach( $targetIds as $id )
        {
          try
          {
            if ( $targetModel instanceof qcl_data_model_AbstractNamedActiveRecord )
            {
              $targetModel->load( (string) $id );
            }
            else
            {
              $targetModel->load( (int) $id );
            }
          }
          catch( qcl_data_model_Exception $e )
          {
            continue;
          }

          /*
           * link and ignore errors from existing links
           */
          try
          {
            $model->linkModel( $targetModel );
          }
          catch( qcl_data_model_Exception $e ){}

        }
      }
    }
  }
}
