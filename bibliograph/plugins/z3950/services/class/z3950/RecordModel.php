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

/**
 * Z3950 record model
 *
 * Dependencies:
 * - php_yaz extension
 */
class z3950_RecordModel
   extends qcl_data_model_db_ActiveRecord
{

  /**
   * model properties
   */
  private $properties = array(

    'citekey' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
    'reftype' => array(
        'check'    => "string",
        'sqltype'  => "varchar(20)"
    ),
   'abstract' => array(
        'check'    => "string",
        'sqltype'  => "text"
    ),
   'address' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'affiliation' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
   'annote' => array(
        'check'    => "string",
        'sqltype'  => "text"
    ),
   'author' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'booktitle' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'subtitle' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'contents' => array(
        'check'    => "string",
        'sqltype'  => "text"
    ),
   'copyright' => array(
        'check'    => "string",
        'sqltype'  => "varchar(150)"
    ),
   'crossref' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
   'date' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
   'doi' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
   'edition' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
   'editor' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'howpublished' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'institution' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'isbn' => array(
        'check'    => "string",
        'sqltype'  => "varchar(30)"
    ),
   'issn' => array(
        'check'    => "string",
        'sqltype'  => "varchar(20)"
    ),
   'journal' => array(
        'check'    => "string",
        'sqltype'  => "varchar(150)"
    ),
   'key' => array(
        'check'    => "string",
        'sqltype'  => "varchar(20)"
    ),
   'keywords' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'language' => array(
        'check'    => "string",
        'sqltype'  => "varchar(20)"
    ),
   'lccn' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'location' => array(
        'check'    => "string",
        'sqltype'  => "varchar(150)"
    ),
   'month' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
   'note' => array(
        'check'    => "string",
        'sqltype'  => "text"
    ),
   'number' => array(
        'check'    => "string",
        'sqltype'  => "varchar(30)"
    ),
   'organization' => array(
        'check'    => "string",
        'sqltype'  => "varchar(150)"
    ),
   'pages' => array(
        'check'    => "string",
        'sqltype'  => "varchar(30)"
    ),
   'price' => array(
        'check'    => "string",
        'sqltype'  => "varchar(30)"
    ),
   'publisher' => array(
        'check'    => "string",
        'sqltype'  => "varchar(150)"
    ),
   'school' => array(
        'check'    => "string",
        'sqltype'  => "varchar(150)"
    ),
   'series' => array(
        'check'    => "string",
        'sqltype'  => "varchar(200)"
    ),
   'size' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)"
    ),
   'title' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)"
    ),
   'type' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)",
        'index'    => false
    ),
   'url' => array(
        'check'    => "string",
        'sqltype'  => "varchar(255)",
        'index'    => false
    ),
   'volume' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)",
        'index'    => false
    ),
   'year' => array(
        'check'    => "string",
        'sqltype'  => "varchar(20)"
    )
 );

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "RecordId";

  /**
   * Relations
   */
  private $relations = array(
    'Record_Search' => array(
      'type'        => QCL_RELATIONS_HAS_ONE,
      'target'      => array( 'modelType' => "search" )
    )
  );


  //-------------------------------------------------------------
  // Init
  //-------------------------------------------------------------

  function __construct( $datasourceModel )
  {
    parent::__construct( $datasourceModel );
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
  }


}
