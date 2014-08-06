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
 * Behavior class providing methods to model a basic tree structure based on an
 * sql database table.
 */
class qcl_data_model_db_TreeNodeModel
  extends qcl_data_model_db_ActiveRecord
//  implements qcl_data_model_ITreeNodeModel
{

  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  private $properties = array(

    'parentId'  => array(
      'check'     => "integer",
      'sqltype'   => "int(11)"
    ),
    'position'  => array(
      'check'     => "integer",
      'sqltype'   => "int(11) NOT NULL",
      'nullable'  => false,
      'init'      => 0
    ),
    'label'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    )
  );

  /**
   * Whether to change the transaction id after a change to the model, true for this model
   * @var bool
   * @override
   */
  protected $incrementTransactionIdAfterUpdate = true;

  //-------------------------------------------------------------
  // init
  //-------------------------------------------------------------

  /**
   * Constructor.
   * @param null|\qcl_data_datasource_DbModel $datasourceModel
   * @return \qcl_data_model_db_TreeNodeModel
   */
  public function __construct( $datasourceModel )
  {
    parent::__construct( $datasourceModel );
    $this->addProperties( $this->properties );
  }


  //-------------------------------------------------------------
  // Getters
  //-------------------------------------------------------------

  /**
   * Getter for the id of the parent node.
   * @return int
   */
  public function getParentId()
  {
    return $this->_get("parentId");
  }

  /**
   * Setter for the id of the parent node.
   * @param $id
   * @return int
   */
  public function setParentId( $id )
  {
    return $this->_set("parentId", $id);
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Returns the data of child nodes of a branch ordered by the order field
   * @param string|null $orderBy
   *    Optional propert name by which the returned data should be ordered.
   *    Defaults to "position".
   * @return array
   */
	function getChildrenData( $orderBy="position" )
	{
	  $query = new qcl_data_db_Query( array(
	   'where'       => array( 'parentId' => $this->id() ),
	   'orderBy'     => $orderBy
	  ) );
	  $this->find( $query );
	  return $this->fetchAll();
	}

  /**
   * Returns the ids of the child node ids optionally ordered by a property
   * @param string|null $orderBy
   *    Optional propert name by which the returned ids should be ordered.
   *    Defaults to "position".
   * @return array
   */
	function getChildIds ( $orderBy="position" )
	{
    $query = new qcl_data_db_Query( array(
     'where'       => array( 'parentId' => $this->id() ),
     'orderBy'     => $orderBy
    ) );
    return $this->getQueryBehavior()->fetchValues("id", $query );
	}

	/**
	 * Finds all model records which are children of the current node
   * @param string|null $orderBy
   *    Optional propert name by which the records should be ordered.
   *    Defaults to "position".
	 * @return qcl_data_db_Query
	 */
	function findChildren( $orderBy="position" )
	{
	  $query = new qcl_data_db_Query( array(
     'where'       => array( 'parentId' => $this->id() ),
     'orderBy'     => $orderBy
    ) );
    $this->lastQuery = $query;
	  $this->getQueryBehavior()->select( $query );
	  return $query;
	}

  /**
   * Returns the number of children of the given node
   * @return int
   */
	public function getChildCount()
	{
		return $this->countWhere( array( "parentId" => $this->id() ) );
	}


	/**
	 * Returns the current position among the node's siblings
	 * @return int
	 */
  public function getPosition()
	{
	  return $this->_get("position");
	}

  /**
   * Change position within folder siblings. Returns itself
   * @param int|string $position New position, either absolute (integer)
   *   or relative ("+1", "-3" etc.)
   * return qcl_data_model_db_TreeNodeModel
   * @return $this
   * @throws InvalidArgumentException
   * @return $this
   */
  function changePosition ( $position )
  {
    $this->checkLoaded();

    /*
     * relative position
     */
    if ( is_string($position) )
    {
      if ( $position[0] == "-" or $position[0] == "+" )
      {
        $position = $this->getPosition() + (int) substr( $position, 1);
      }
      else
      {
        throw new InvalidArgumentException("Invalid relative position");
      }
    }
    elseif ( ! is_int( $position ) )
    {
      throw new InvalidArgumentException("Position must be relative or integer");
    }

    /*
     * change to parent node
     */
    $id = $this->id();
    $parentId = $this->getParentId();
    $where = array( 'parentId' => $parentId ) ;
    $childCount = $this->countWhere( $where );
    $query = $this->findWhere( $where, "position" );

    /*
     * check position
     */
    if ( $position < 0 or $position >= $childCount )
    {
      throw new InvalidArgumentException("Invalid position '$position'");
    }

    /*
     * iterate over the parent node's children
     */
    $index = 0;
    while ( $this->loadNext($query) )
    {
      if ( $this->id() == $id )
      {
        $this->setPosition( $position );
        //$this->debug(sprintf("Setting node %s to position %s",$this->getLabel(), $position ),__CLASS__,__LINE__);
        $this->save();
      }
      else
      {
        if ( $index == $position )
        {
          //$this->debug("Skipping $index ",__CLASS__,__LINE__);
          $index++; // skip over target position
        }
        //$this->debug(sprintf( "Setting sibling node %s to position %s", $this->getLabel(), $index),__CLASS__,__LINE__);
        $this->setPosition( $index++ );
        $this->save();
      }
    }

    /*
     * switch back to original record
     */
    $this->load( $id );
    return $this;
  }

   /**
    * Change parent node
    * @param int $parentId  New parent node id
    * @return int Old parent id
    */
	public function changeParent( $parentId )
	{
		$oldParentId = $this->getParentId();
    $this->setParentId( $parentId );
    $this->save();
    return $oldParentId;
	}

  /**
   * Returns the path of a node in the folder hierarchy as a
   * string of the node labels, separated by the a given character
   *
   * @param string $separator
   *    Separator character, defaults to "/"
   * @return string
   */
  public function getLabelPath( $separator="/" )
  {

    $id= $this->id();

    /*
     * get path of parent if any
     */
    $path = str_replace( $separator, '\\' . $separator, $this->getLabel() );
    $count = 0;

    while (  $parentId = $this->getParentId() and $count++ < 10 )
    {
      $this->load( $parentId );
      $label = str_replace( $separator, '\\' . $separator, $this->getLabel() );
      $path = $label . $separator . $path;
    }

    $this->load($id);

    return $path;
  }

  /**
   * Returns the path of a node in the folder hierarchy,
   * as an array of ids.
   *
   * @throws qcl_core_NotImplementedException
   * @return string
   */
  public function getIdPath()
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Returns the id of a node given its label path
   * @param string $path
   * @param string $separator Separator character, defaults to "/"
   * @throws qcl_core_NotImplementedException
   * @return int|null The id of the node or null if node does not exist
   */
  public function getIdByPath ( $path, $separator="/" )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Creates nodes along the path if they don't exist
   * @param string $path
   * @param string $separator Separator character, defaults to "/"
   * @throws qcl_core_NotImplementedException
   * @return int Node id
   */
  public function createPath( $path, $separator="/" )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }
}
