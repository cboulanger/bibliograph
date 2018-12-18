<?php

namespace app\models;

use app\controllers\AppController;
use lib\channel\BroadcastEvent;
use lib\channel\MessageEvent;
use lib\exceptions\UserErrorException;
use Yii;
use InvalidArgumentException;

use app\controllers\FolderController;

use app\models\Reference;
use lib\models\ITreeNode;
use yii\base\Event;
use yii\db\Exception;

/**
 * This is the model class for table "database1_data_Folder".
 *
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
class Folder extends \lib\models\BaseModel //implements ITreeNode
{
  //@todo: name as client-side message?
  const MESSAGE_CLIENT_UPDATE = "folder.node.update";
  const MESSAGE_CLIENT_ADD    = "folder.node.add";
  const MESSAGE_CLIENT_DELETE = "folder.node.delete";
  const MESSAGE_CLIENT_MOVE   = "folder.node.move";
  const MESSAGE_CLIENT_PRUNE  = "folder.node.prune";

  /**
   * The type of the model when part of a datasource
   */
  public static $modelType = "folder";

  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    if (static::getDatasource()) {
      return static::getDatasource()->namedId . "_data_Folder";
    }
    return '{{%data_Folder}}';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [
        ['parentId','position','opened','searchfolder','locked','hidden','markedDeleted','childCount','referenceCount'],
        'default', 'value'=> 0
      ],
      [
        ['searchable','public'],
        'default', 'value'=> 1
      ],
      [
        ['parentId', 'position', 'searchable', 'searchfolder', 'public', 'opened', 'locked', 'hidden', 'markedDeleted', 'childCount', 'referenceCount'],
        'integer'
      ],
      [
        ['label', 'description', 'path'],
        'string', 'max' => 100
      ],
      [
        ['type', 'createdBy'],
        'string',
        'max' => 20
      ],
      [
        ['query'],
        'string',
        'max' => 255
      ],
      [
        ['owner'],
        'string', 'max' => 30
      ],
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
   * @return array
   */
  public function getFormData()
  {
    return  [
      'label' => [
        'label' => Yii::t('app', "Folder Title"),
        'type' => "TextField",
        'width' => 800
      ],
      'description' => [
        'label' => Yii::t('app', "Description"),
        'type' => "TextArea",
        'lines' => 2
      ],
      'public' => [
        'label' => Yii::t('app', "Is folder publically visible?"),
        'type' => "SelectBox",
        'options' => [
          ['label' => Yii::t('app', "No"), 'value' => 0],
          ['label' => Yii::t('app', "Yes"), 'value' => 1],
        ]
      ],
      //    'searchable'  => array(
      //      'label'     => Yii::t('app', "Publically searchable?"),
      //      'type'      => "SelectBox",
      //      'options'   => array(
      //        array( 'label' => "Folder is searchable", 'value' => 1 ),
      //        array( 'label' => "Folder is not searchable (Currently not implemented)", 'value' => 0 )
      //      )
      //    ),
      'searchfolder' => [
        'label' => Yii::t('app', "Search folder?"),
        'type' => "SelectBox",
        'options' => [
          ['label' => Yii::t('app', "On, Use query to determine content"), 'value' => 1],
          ['label' => Yii::t('app', "Off"), 'value' => 0]
        ]
      ],
      'virtsub' => [
        'label' => Yii::t('app', "Virtual subfolders"),
        'type' => "SelectBox",
        'options' => (array) $this->getIndexFieldsOptions(),
        'value' => "",
        'marshal' => function($value, $model, &$formData ){
          if( str_contains($model->query, 'virtsub:') ){
            $formData['query']['enabled'] = false;
            return substr($model->query,8);
          }
          return $value;
        },
        'unmarshal' => function($value, &$data){
          if(trim($value)){
            $data['query'] = "virtsub:" . $value;
          }
          return null; // do not set null value
        }
      ],
      'query' => [
        'label' => Yii::t('app', "Query"),
        'type' => "TextArea",
        'lines' => 5
      ],
      'opened' => [
        'label' => Yii::t('app', "Opened?"),
        'type' => "SelectBox",
        'options' => [
          ['label' => Yii::t('app', "Folder is closed by default"), 'value' => 0],
          ['label' => Yii::t('app', "Folder is opened by default"), 'value' => 1]
        ]
      ],
      'position' =>[
        'label' => Yii::t('app',"Position"),
        "type"  => "spinner",
        "min"   => 0,
        "max"   => 100
      ]
    ];
  }

  protected function getIndexFieldsOptions()
  {
    $options = [[
      'label' => Yii::t('app',"No virtual subfolders"),
      'value' => ""
    ]];
    $schema = Datasource::in(self::getDatasource()->namedId, "reference")::getSchema();
    $indexNames = $schema->getIndexNames();
    sort($indexNames);
    foreach( $indexNames as $indexName ){
      $options[] = [
        'label' => $indexName,
        'value' => $schema->getIndexFields($indexName)[0]
      ];
    }
    return $options;
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getFolderReferences()
  {
    Folder_Reference::setDatasource(static::getDatasource());
    return $this->hasMany(Folder_Reference::class, ['FolderId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  public function getReferences()
  {
    Reference::setDatasource(static::getDatasource());
    return $this->hasMany(Reference::class, ['id' => 'ReferenceId'])->via('folderReferences');
  }

  /*
  ---------------------------------------------------------------------------
     EVENT HANDLERS
  ---------------------------------------------------------------------------
  */

  /**
   * @todo Check if still needed
   * @return int
   */
  protected function getTransactionId()
  {
    return 0;
  }

  /**
   * Creates an Event that will be forwarded to the client to trigger a
   * change in the folder tree
   */
  protected function createUpdateNodeEvent($nodeData)
  {
    return new BroadcastEvent([
      'name' => static::MESSAGE_CLIENT_UPDATE,
      'data' => [
        'datasource' => static::getDatasource()->namedId,
        'modelType' => static::$modelType,
        'nodeData' => $nodeData,
        'transactionId' => $this->getTransactionId()
      ]]);
  }

  /**
   * Updates a parent node , i.e. recalculates the node children etc.
   * By default, opens the node
   * @param int|null $parentId
   *    Optional id of parent node. If omitted (normal case), the
   *    parentId of the current model instance is used
   * @param boolean $openNode
   *    Optional flag to open the parent node on the client. This doesn't
   *    save the "opened" state.
   * @throws \yii\db\Exception
   */
  public function updateParentNode($parentId=null, $openNode=true)
  {
    if( ! $parentId ){
      $parentId = $this->parentId;
    }
    $parent = static::findOne(['id' => $parentId]);
    if( ! $parent ) return;
    //Yii::debug("Updating parent node " . $parent->label, __METHOD__, __METHOD__);
    $parent->getChildCount(true); // this saves the parent
    $nodeData = FolderController::getNodeDataStatic($parent);
    if( $openNode ){
      $nodeData['bOpened'] = true;
    }
    //Yii::debug($nodeData, __METHOD__);
    // update new parent on client
    Yii::$app->eventQueue->add($this->createUpdateNodeEvent($nodeData));
  }

  /**
   * Triggered when a record is saved to dispatch events.
   * Inserts are dealt with in _afterInsert()
   *
   * @param bool $insert
   * @param array $changedAttributes
   * @return boolean
   * @throws Exception
   * @throws \Throwable
   */
  public function afterSave($insert, $changedAttributes)
  {
    // parent implemenattion
    parent::afterSave($insert, $changedAttributes);

    // do no emit events if in console mode
    if( Yii::$app->request->isConsoleRequest ) return true;

    // inserts
    if ($insert) {
      //Yii::debug("Inserting " . $this->label, __METHOD__);
      $this->_afterInsert();
      return true;
    }
    // dispatch events
    //Yii::debug("Updating " . $this->label . " " . json_encode($changedAttributes));
    foreach ($changedAttributes as $key => $oldValue) {
      switch ($key) {
        case "parentId":
          // skip if no parent id had been set (is the case when adding a node) or no valid user
          if ($oldValue===null or ! Yii::$app->user->getIdentity() ) return false;
          // update parents
          try {
            $this->updateParentNode();
            // update old parent also
            if( $oldValue){
              $this->updateParentNode($oldValue,false);
            }
          } catch (Exception $e) {
            Yii::error($e);
          }
          // move node
          Yii::$app->eventQueue->add(new BroadcastEvent([
            'name' => static::MESSAGE_CLIENT_MOVE,
            'data' => [
              'datasource' => static::getDatasource()->namedId,
              'modelType' => "folder",
              'nodeId' => $this->id,
              'parentId' => $this->parentId,
              'transactionId' => $this->getTransactionId()
            ]]));
          break;
      } // end switch
    } // end foreach

    // if attributes have changed and we have a valid user, update the node
    if (count($changedAttributes) > 0 and Yii::$app->user->getIdentity()) {
      try {
        $nodeData = FolderController::getNodeDataStatic($this);
      } catch (Exception $e) {
        throw new UserErrorException($e->getMessage(),null, $e);
      }
      Yii::$app->eventQueue->add($this->createUpdateNodeEvent($nodeData));
    }
    // add virtual subfolders
    $this->_createVirtualSubfoldersOnDemand(!!$insert);
    return true;
  }

  /**
   * Dispatches a message that initiates the loading of the virtual subfolders of this node,
   * if it has any.
   * @param bool $pruneFirst If true, remove existing subfolders first. Defaults to false.
   */
  protected function _createVirtualSubfoldersOnDemand($pruneFirst=false){
    if (str_contains( $this->query, "virtsub:" )){
      if ($pruneFirst) {
        Yii::$app->eventQueue->add(new MessageEvent([
          'name' => static::MESSAGE_CLIENT_PRUNE,
          'data' => [ 'datasource' => static::getDatasource()->namedId, 'id' => $this->id]
        ]));
      }
      Yii::$app->eventQueue->add(new MessageEvent([
        'name' => AppController::MESSAGE_EXECUTE_JSONRPC,
        'data' => ["folder", "create-virtual-folders", [static::getDatasource()->namedId, $this->id]]
      ]));
    }
  }

  /**
   * Called when a new Active Record has been created
   *
   * @return void
   * @throws Exception
   * @throws \Throwable
   */
  protected function _afterInsert()
  {
    // skip if we don't have a logged-in user (e.g. in tests)
    if (!Yii::$app->user->getIdentity()) return;
    if($this->parentId){
      $this->updateParentNode();
    }
    //@todo move to method
    Yii::$app->eventQueue->add(new BroadcastEvent([
      'name' => static::MESSAGE_CLIENT_ADD,
      'data' => [
        'datasource'  => static::getDatasource()->namedId,
        'modelType'   => static::$modelType,
        'nodeData'    => FolderController::getNodeDataStatic($this),
        'transactionId' => $this->getTransactionId()
      ]]));
  }

  /**
   * Called after an ActiveRecord has been deleted
   *
   * @return void
   * @throws Exception
   */
  public function afterDelete()
  {
    parent::afterDelete();
    $this->updateParentNode();
    Yii::$app->eventQueue->add(new BroadcastEvent([
      'name' => static::MESSAGE_CLIENT_DELETE,
      'data' => [
        'datasource' => static::getDatasource()->namedId,
        'modelType' => static::$modelType,
        'nodeId' => $this->id,
        'transactionId' => $this->getTransactionId()
      ]]));
  }

  //-------------------------------------------------------------
  // Protected methods
  //-------------------------------------------------------------

  /**
   * Returns the Folder objects of subfolders of this folder optionally ordered by a property
   * @param string|null $orderBy
   *    Optional propert name by which the returned ids should be ordered.
   *    Defaults to "position".
   * @return \yii\db\ActiveQuery
   */
  protected function getChildrenQuery($orderBy = "position")
  {
    return static::find()
      ->select("id")
      ->where(['parentId' => $this->id])
      ->orderBy($orderBy);
  }

  //-------------------------------------------------------------
  // ITreeNode Interface
  //-------------------------------------------------------------  

  /**
   * Returns the Folder objects of subfolders of this folder optionally ordered by a property
   * @param string|null $orderBy
   *    Optional propert name by which the returned ids should be ordered.
   *    Defaults to "position".
   * @return Folder[]|array
   */
  public function getChildren($orderBy = "position")
  {
    return $this->getChildrenQuery($orderBy)->all();
  }

  /**
   * Returns the ids of the child node ids optionally ordered by a property
   * @param string|null $orderBy
   *    Optional propert name by which the returned ids should be ordered.
   *    Defaults to "position".
   * @return array of ids
   */
  function getChildIds($orderBy = "position")
  {
    return $this->getChildrenQuery($orderBy)->select('id')->column();
  }

  /**
   * Returns the data of child nodes of a branch ordered by the order field
   * @param string|null $orderBy
   *    Optional propert name by which the returned data should be ordered.
   *    Defaults to "position".
   * @return array
   */
  function getChildrenData($orderBy = "position")
  {
    $query = static::find()
      ->where(['parentId' => $this->id])
      ->orderBy($orderBy);
    return $query->asArray()->all();
  }

  /**
   * Returns the number of children
   * @param bool $update
   *    If $update If true, recalculate the child count. Defaults to false.
   * @return int
   * @throws Exception
   */
  public function getChildCount($update = false)
  {
    if ($update or $this->childCount === null) {
      $this->childCount = (int) $this->getChildrenQuery()->count();
      $this->save();
    }
    return $this->childCount;
  }

  /**
   * Returns the number of references linked to the folder
   * @param bool $update If true, calculate the reference count again. Defaults to false
   * @return int
   * @throws Exception
   */
  public function getReferenceCount($update = false)
  {
    if ($update or $this->referenceCount === null) {
      $this->referenceCount = $this->getReferences()->count();
      $this->save();
    }
    return $this->referenceCount;
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
  function changePosition($position)
  {
    // relative position
    if (is_string($position)) {
      if ($position[0] == "-" or $position[0] == "+") {
        $position = $this->position + (int)$position;
      } else {
        throw new InvalidArgumentException("Invalid relative position");
      }
    } elseif (!is_int($position)) {
      throw new InvalidArgumentException("Position must be relative or integer");
    }

    // siblings
    $query = Folder::find()->where(['parentId' => $this->parentId])->orderBy('position');
    $siblingCount = $query->count();

    // check position
    if ($position < 0 or $position >= $siblingCount) {
      throw new InvalidArgumentException("Invalid position");
    }

    // iterate over the parent node's children
    $index = 0;
    foreach ($query->all() as $sibling) {
      // it's me...
      if ($this->id == $sibling->id) {
        $sibling->position = $position;
        //$this->debug(sprintf("Setting node %s to position %s",$this->getLabel(), $position ),__CLASS__,__LINE__);
      } else {
        if ($index == $position) {
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
   * @param Folder
   * @return int Old parent id
   * @throws Exception
   */
  public function setParent(Folder $parentFolder)
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
  public function labelPath($separator = "/")
  {
    // escape existing separator characters in label
    $path = str_replace($separator, '\\' . $separator, $this->label);
    $parentId = $this->parentId;
    while ($parentId) {
      $folder = Folder::findOne(['id' => $parentId]);
      if (!$folder) throw new \RuntimeException("Folder #$parentId does not exist.");
      $label = str_replace($separator, '\\' . $separator, $folder->label);
      $path = $label . $separator . $path;
      $parentId = $folder->parentId;
    }
    return $path;
  }
}