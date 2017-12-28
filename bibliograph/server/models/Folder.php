<?php

namespace app\models;

use Yii;
use InvalidArgumentException;
use app\models\BaseModel;
use app\models\Reference;

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
class Folder extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'datasource_data_Folder';
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
  // Relations
  //-------------------------------------------------------------

  /**
   * @return \yii\db\ActiveQuery
   */ 
  protected function getFolderReferences()
  {
    return $this->hasMany(Folder_Reference::className(), ['FolderId' => 'id'] );
  }  

  /**
   * @return \yii\db\ActiveQuery
   */ 
  public function getReferences()
  {
    return $this->hasMany(Reference::className(), ['id' => 'ReferenceId'])->via('folderReferences');
  }

  //-------------------------------------------------------------
  // Public API
  //-------------------------------------------------------------

  /**
   * Returns the Folder objects of subfolders of this folder optionally ordered by a property
   * @param string|null $orderBy
   *    Optional propert name by which the returned ids should be ordered.
   *    Defaults to "position".
   * @return \yii\db\ActiveQuery
   */
	protected function getChildrenQuery( $orderBy="position" )
	{
    return Folder::find()
      ->select("id")
      ->where([ 'parentId' => $this->id ])  
      ->orderBy($orderBy);
	}

  /**
   * Returns the Folder objects of subfolders of this folder optionally ordered by a property
   * @param string|null $orderBy
   *    Optional propert name by which the returned ids should be ordered.
   *    Defaults to "position".
   * @return \yii\db\ActiveQuery[]|null
   */
  public function getChildren( $orderBy="position" )
  {
    return $this->getChildrenQuery( $orderBy )->all();
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
    return $this->getChildrenQuery($orderBy)->column();
	}

  /**
   * Returns the data of child nodes of a branch ordered by the order field
   * @param string|null $orderBy
   *    Optional propert name by which the returned data should be ordered.
   *    Defaults to "position".
   * @return array
   */
	function getChildrenData( $orderBy="position" )
	{
    $query = Folder::find()
      ->where(['parentId'=>$this->id])
      ->orderBy($orderBy);
	  return $query->asArray()->all();
	}

  /**
   * Returns the number of children 
   * @param bool|\If $update If true, recalculate the child count. Defaults to false.
   * @return int
   */
  public function getChildCount($update=false)
  {
    if ( $update )
    {
      $this->childCount = $this->getChildrenQuery()->count();
      $this->save();
    }
    return $this->childCount;
  }

	/**
	 * Returns the current position among the node's siblings
	 * @return int
	 */
  public function getPosition()
	{
	  return $this->position;
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
  function changePosition( $position )
  {
    // relative position
    if ( is_string($position) )
    {
      if ( $position[0] == "-" or $position[0] == "+" )
      {
        $position = $this->position + (int) $position;
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

    // siblings
    $query = Folder::find()->where( ['parentId' => $this->parentId] )->orderBy('position');
    $siblingCount = $query->count(); 

    // check position
    if ( $position < 0 or $position >= $siblingCount )
    {
      throw new InvalidArgumentException("Invalid position");
    }

    // iterate over the parent node's children
    $index = 0;
    foreach ( $query->all() as $sibling )
    {
      // it's me...
      if ( $this->id == $sibling->id )
      {
        $sibling->position = $position;
        //$this->debug(sprintf("Setting node %s to position %s",$this->getLabel(), $position ),__CLASS__,__LINE__);
      }
      else
      {
        if ( $index == $position )
        {
          //$this->debug("Skipping $index ",__CLASS__,__LINE__);
          $index++; // skip over target position
        }
        //$this->debug(sprintf( "Setting sibling node %s to position %s", $this->getLabel(), $index),__CLASS__,__LINE__);
        $sibling->position = $index++;
      }
      $sibling->save();
    }
    return $this;
  }

   /**
    * Set parent node
    * @param \app\models\Folder
    * @return int Old parent id
    */
	public function setParent( \app\models\Folder $parentFolder )
	{
		$oldParentId = $this->parentId;
    $this->parentId = $parentFolder->id;
    $this->save();
    return $oldParentId;
	}

  /**
   * Returns the path of a node in the folder hierarchy as a
   * string of the node labels, separated by the a given character. If that character
   * exists in the folder labels, these occurrences will be escaped with '\'
   *
   * @param string $separator
   *    Separator character, defaults to "/"
   * @return string
   */
  public function labelPath( $separator="/" )
  {
    // escape existing separator characters in label
    $path = str_replace( $separator, '\\' . $separator, $this->label );
    $parentId= $this->parentId;
    while( $parentId )
    {
      $folder = Folder::findOne( ['id' => $parentId]);
      if( ! $folder ) throw new LogicException("Folder #$parentId does not exist.");
      $label = str_replace( $separator, '\\' . $separator, $folder->label );
      $path = $label . $separator . $path;
      $parentId = $folder->parentId;
    }
    return $path;
  }
}
