<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "database1_data_Folder".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property integer $parentId
 * @property integer $position
 * @property string $label
 * @property string $type
 * @property string $description
 * @property integer $searchable
 * @property integer $searchfolder
 * @property string $query
 * @property integer $public
 * @property integer $opened
 * @property integer $locked
 * @property string $path
 * @property string $owner
 * @property integer $hidden
 * @property string $createdBy
 * @property integer $markedDeleted
 * @property integer $childCount
 * @property integer $referenceCount
 */
class Folder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'database1_data_Folder';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['parentId', 'position'], 'required'],
            [['parentId', 'position', 'searchable', 'searchfolder', 'public', 'opened', 'locked', 'hidden', 'markedDeleted', 'childCount', 'referenceCount'], 'integer'],
            [['label', 'description', 'path'], 'string', 'max' => 100],
            [['type', 'createdBy'], 'string', 'max' => 20],
            [['query'], 'string', 'max' => 255],
            [['owner'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created' => Yii::t('app', 'Created'),
            'modified' => Yii::t('app', 'Modified'),
            'parentId' => Yii::t('app', 'Parent ID'),
            'position' => Yii::t('app', 'Position'),
            'label' => Yii::t('app', 'Label'),
            'type' => Yii::t('app', 'Type'),
            'description' => Yii::t('app', 'Description'),
            'searchable' => Yii::t('app', 'Searchable'),
            'searchfolder' => Yii::t('app', 'Searchfolder'),
            'query' => Yii::t('app', 'Query'),
            'public' => Yii::t('app', 'Public'),
            'opened' => Yii::t('app', 'Opened'),
            'locked' => Yii::t('app', 'Locked'),
            'path' => Yii::t('app', 'Path'),
            'owner' => Yii::t('app', 'Owner'),
            'hidden' => Yii::t('app', 'Hidden'),
            'createdBy' => Yii::t('app', 'Created By'),
            'markedDeleted' => Yii::t('app', 'Marked Deleted'),
            'childCount' => Yii::t('app', 'Child Count'),
            'referenceCount' => Yii::t('app', 'Reference Count'),
        ];
    }

  /**
   * Adds the form data for this model
   * @param $datasourceModel
   * @return void
   */
  protected function addFormData( $datasourceModel )
  {
    $this->formData =  array(
      'label'  => array(
        'label'     => _("Folder Title"),
        'type'      => "TextField"
      ),
      'description'  => array(
        'label'     => _("Description"),
        'type'      => "TextArea",
        'lines'     => 2
      ),
      'public'  => array(
        'label'     => _("Is folder publically visible?"),
        'type'      => "SelectBox",
        'options'   => array(
          array( 'label' => _("Yes"), 'value' => true ),
          array( 'label' => _("No"), 'value' => false )
        )
      ),
  //    'searchable'  => array(
  //      'label'     => _("Publically searchable?"),
  //      'type'      => "SelectBox",
  //      'options'   => array(
  //        array( 'label' => "Folder is searchable", 'value' => true ),
  //        array( 'label' => "Folder is not searchable (Currently not implemented)", 'value' => false )
  //      )
  //    ),
      'searchfolder'  => array(
        'label'     => _("Search folder?"),
        'type'      => "SelectBox",
        'options'   => array(
          array( 'label' => _("On, Use query to determine content"), 'value' => true ),
          array( 'label' => _("Off"), 'value' => false )
        )
      ),
      'query'  => array(
        'label'     => _("Query"),
        'type'      => "TextArea",
        'lines'     => 3
      ),

      'opened'  => array(
        'label'     => _("Opened?"),
        'type'      => "SelectBox",
        'options'   => array(
          array( 'label' => _("Folder is opened by default"), 'value' => true ),
          array( 'label' => _("Folder is closed by default"), 'value' => false )
        )
      )
    );
  }

  //-------------------------------------------------------------
  // Public API
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
    $query = Folder::find(['parentId'=>$this->id])->orderBy($orderBy);
	  return $query->asArray()->all();
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
    $query = Folder::find(['parentId' => $this->id ] )->orderBy($orderBy);
    
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
   * Return 
   * @param bool|\If $update If true, recalculate the child count. Defaults to false.
   * @return int
   */
  public function getChildCount($update=false)
  {
    if ( $update )
    {
      $childCount = $this->countWhere( array( "parentId" => $this->id() ) );
      $this->set("childCount", $childCount)->save();
      return $childCount;
    }
    else
    {
      return $this->_get("childCount");
    }
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
