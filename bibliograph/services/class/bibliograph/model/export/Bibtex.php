<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("bibliograph_model_export_AbstractExporter");
qcl_import("bibliograph_schema_BibtexSchema");

/**
 *
 */
class bibliograph_model_export_Bibtex
  extends bibliograph_model_export_AbstractExporter
{
  /**
   * The id of the format
   * @var string
   */
  protected $id = "bibtex";

  /**
   * The name of the format
   * @var string
   */
  protected $name ="BibTeX";

  /**
   * The type of the format
   * @var string
   */
  protected $type = "bibliograph";


  /**
   * The file extension of the format
   * @var string
   */
  protected $extension = "bib";

  /**
   * The data schema model
   * @var bibliograph_schema_BibtexSchema
   */
  protected $schema;

  /**
   * Construcotr
   */
  public function __construct()
  {
    $this->schema = bibliograph_schema_BibtexSchema::getInstance();
  }

  /**
   * Converts an array of bibliograph record data to a bibtex string
   *
   * @param array $data
   *     Reference data
   * @param array|null $exclude
   *     If given, exclude the given fields
   * @return string
   *     Bibtex string
   */
  function export( $data, $exclude=array() )
  {
    qcl_assert_array( $data, "Invalid data");
    $bibtex="";

    /*
     * iterate through each record
     */
    foreach( $data as $ref )
    {
      $reftype = $ref['reftype']; unset( $ref['reftype'] );
      $citekey = $ref['citekey']; unset( $ref['citekey'] );

      /*
       * construct citekey if missing
       */
      if ( ! $citekey )
      {
        $creator = explode ( ",", either ( $ref['author'], $ref['editor'] ) );
        $citekey = str_replace(" ","", trim ( $creator[0] ) . $ref ['year'] );
      }

      /*
       * non-standard entry type "collection" needs to be
       * converted into "book"
       */
      if ( $reftype == "collection" )
      {
        $reftype = "book";
        $ref['editor'] = $ref['author'];
        unset($ref['author']);
      }

      /*
       * non-standard  entry type "journal"
       * is converted into "article"
       */
      if ( $reftype == "journal" )
      {
        $reftype = "article";
        if ( empty( $ref['title'] ) )
        {
          $ref['title'] = _("Special Issue");
        }
      }

      /*
       * entry header
       */
      $bibtex .=  '@' . $reftype . '{' . $citekey . ',';

      /*
       * bibtex fields
       */
      foreach ( $ref as $key => $value )
      {
        /*
         * skip empty and excluded fields
         */
        if ( ! $value or ( is_array( $exclude ) and  in_array( $key, $exclude ) ) )
        {
          continue;
        }

        /*
         * check if this is a bibtex field
         */
        try
        {
          $fieldData = $this->schema->getFieldData( $key );
          if( ! isset($fieldData['bibtex']) or $fieldData['bibtex']===false )
          {
            continue;
          }
        }
        catch( InvalidArgumentException $e )
        {
          continue;
        }

        /*
         * several authors should have "and"
         */
        if( $key == "author" or $key=="editor" )
        {
          $value = str_replace(";"," and ",$value);
        }

        /*
         * clean up
         */
        $value = str_replace("  "," ", str_replace( "\n"," ", $value) );

        $bibtex .= "\n\t".$key.' = {'. $value .'},';
      }
      $bibtex = substr($bibtex,0,-1)."\n".'}'."\n\n";
    }
    return $bibtex;
  }

  /**
   * Converts an array with utf-8-encoded bibtex array data to
   * bibtexxml as define at http://bibtexml.sourceforge.net/
   * @param array $data
   * @return string
   */
  function toXml ($data=null)
  {
    if ( $data == null )
    {
      $data = $this->data;
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<file xmlns="http://bibtexml.sf.net/">';

    foreach ( $data as $record )
    {
      // start of record
      $citekey = $record['citekey'];
      $xml .= "\n\t" .'<entry id="' . $citekey . '">';
      unset($record['citekey']);

      // reference type
      $reftype = $record['reftype'];
      $xml .= "\n\t\t" . "<$reftype>";
      unset($record['reftype']);

      foreach( $record as $key => $values )
      {
        if ( preg_match('/<|>|&/',$values) )
        {
          $values = "<![CDATA[" . $values . "]]>";
        }
        $xml .= "\n\t\t" ."<$key>$values</$key>";
      }
      $xml .= "\n\t\t" . "</$reftype>";
      $xml .= "\n\t" . '</entry>';
    }
    // end of records
    $xml .= "\n" . '</file>';
    return $xml;
  }
}
?>