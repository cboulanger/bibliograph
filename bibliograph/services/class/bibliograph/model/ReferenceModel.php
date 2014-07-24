<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_model_db_ActiveRecord" );

/**
 * Default reference model
 */
class bibliograph_model_ReferenceModel
  extends qcl_data_model_db_ActiveRecord
{

  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  protected $tableName = "data_Reference";

  /**
   * model properties
   */
  private $properties = array(

    'citekey' => array(
      'check'    => "string",
      'sqltype'  => "varchar(50)"
    ),
    // @todo rename to recordtype
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
    'translator' => array(
      'check'    => "string",
      'sqltype'  => "varchar(100)"
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
    ),


    /* *********** Meta-Properties ************* */

   'createdBy' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)",
        'index'    => false
    ),
   'modifiedBy' => array(
        'check'    => "string",
        'sqltype'  => "varchar(50)",
        'index'    => false
    ),
   'hash' => array(
        'check'    => "string",
        'sqltype'  => "varchar(40)",
        'index'    => false
    ),
   'markedDeleted' => array(
        'check'    => "boolean",
        'sqltype'  => "tinyint(1)",
    		'nullable' => false,
        'init'     => false,
        'index'    => false
    ),
   'attachments' => array(
        'check'    => "int",
        'sqltype'  => "int",
        'init'     => 0,
        'index'    => false
    ),
 );

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "ReferenceId";

  /**
   * Relations
   */
  private $relations = array(
    'Folder_Reference' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "bibliograph_model_FolderModel" )
    )
  );

  /**
   * Indexes
   */
  private $indexes = array(
    "fulltext" => array(
      "type"        => "fulltext",
      "properties"  => array(
        'abstract','annote', 'author', 'booktitle', 'subtitle', 'contents',
        'editor','howpublished', 'journal','keywords', 'note','publisher',
        'school', 'title', 'year'
      )
    ),
    "basic" => array(
       "type"       => "fulltext",
       "properties" => array("author","title","year","editor")
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
    $this->addIndexes( $this->indexes );
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Returns the schema object used by this model
   * @return bibliograph_schema_BibtexSchema
   */
	function getSchemaModel()
	{
	  qcl_import( "bibliograph_schema_BibtexSchema" );
	  return bibliograph_schema_BibtexSchema::getInstance();
	}

	/**
	 * Getter for reference type
	 * @return string
	 */
	function getReftype()
	{
	  return $this->_get("reftype");
	}

	/**
	 * Overridden to set "createdBy" column
	 * @see qcl_data_model_AbstractActiveRecord#create()
	 */
	function create( $data=null )
	{
	  $activeUser = $this->getApplication()->getAccessController()->getActiveUser();
	  $data['createdBy'] = $activeUser->namedId();
	  return parent::create( $data );
	}

	/**
	 * Overridden to set "modifiedBy" column
	 * @see qcl_data_model_AbstractActiveRecord#save()
	 */
	function save()
	{
	  $activeUser = $this->getApplication()->getAccessController()->getActiveUser();
	  $this->set("modifiedBy", $activeUser->namedId() );
	  return parent::save();
	}

  /**
   * @return string
   */
  function getAuthor()
  {
    return $this->_get("author");
  }

  /**
   * @return string
   */
  function getTitle()
  {
    return $this->_get("title");
  }

  /**
   * @return string
   */
  function getYear()
  {
    return $this->_get("year");
  }

  /**
   * Returns author or editor depending on reference type
   * @return string
   */
  function getCreator()
  {
    $author = $this->getAuthor();
    return empty($author) ? $this->_get("editor") : $author;
  }

  /**
   * Computes the citation key from the record data.
   * The format is Author-Year-TitleWord or Author1+Author2+Author3-Year-Titleword
   * @return string
   */
  function computeCiteKey()
  {
    $creators = explode(trim(BIBLIOGRAPH_VALUE_SEPARATOR),$this->getCreator());
    $lastNames = array();
    foreach($creators as $name)
    {
      $parts = explode(",", $name);
      $lastNames[] = trim($parts[0]);
    }
    $citekey = implode("+", $lastNames);

    $citekey .= "-" . $this->getYear();

    $titlewords = explode(" ",$this->getTitle());
    while ( count($titlewords) and strlen($titlewords[0]) < 4 )
    {
      array_shift($titlewords);
    }
    if( count($titlewords) )
    {
      $citekey .=  "-" . $titlewords[0];
    }
    return $citekey;
  }

	/**
	 * Selects potential duplicates
	 * @param $threshold The threshold score to count as a duplicate
	 * @return array
   *    Returns an array of match scores in the order of the found records
   * @todo this works only with MySQL, must be abstracted to support other backends
	 */
	function findPotentialDuplicates($threshold=50)
	{
    $queryBehavior = $this->getQueryBehavior();
    $adapter = $this->getQueryBehavior()->getAdapter();

    $author = $this->getAuthor();
    $title  = $this->getTitle();
    $year   = $this->getYear();

    $match = $adapter->fullTextSql(
      $queryBehavior->getTableName(),
      "basic", "$author $title $year", "fuzzy"
    );
    $table = $queryBehavior->getTableName();
    $id = $this->id();
    $sql = "
      SELECT id,
        $match AS score,
        ($match / maxScore)*100 AS normalisedScore
      FROM $table,
        (SELECT MAX($match) AS maxScore FROM $table) AS maxScoreTable
      WHERE
        $match
      HAVING normalisedScore > $threshold AND id != $id
      ORDER BY score DESC
    ";
    $rows = $adapter->fetchAll($sql);
    //qcl_log_Logger::getInstance()->info($sql);
    //qcl_log_Logger::getInstance()->info(print_r($rows,true));
    $ids = array();
    $scores = array();
    foreach( $rows as $row )
    {
        $ids[]    = $row['id'];
        $scores[] = $row['normalisedScore'];
    }
    if( count($ids) )
    {
      $this->lastQuery = $queryBehavior->selectIds( $ids );
    }
    return $scores;
	}
}
?>