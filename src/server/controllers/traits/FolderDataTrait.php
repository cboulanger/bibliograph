<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 27.02.18
 * Time: 10:05
 */

namespace app\controllers\traits;

use InvalidArgumentException;
use Yii;
use app\models\Folder;

trait FolderDataTrait
{


  /**
   * Icons
   * @todo REally in a trait?
   * @var array
   */
  static $icon = array(
    "closed"          => null,
    "open"            => null,
    "default"         => "icon/16/places/folder.png",
    "search"          => "icon/16/apps/utilities-graphics-viewer.png",
    "trash"           => "icon/16/places/user-trash.png",
    "trash-full"      => "icon/16/places/user-trash-full.png",
    "marked-deleted"  => "icon/16/actions/folder-new.png",
    "public"          => "icon/16/places/folder-remote.png",
    "favorites"       => "icon/16/actions/help-about.png"
  );

  /**
   * Returns the icon for a given folder type
   * @param string $type
   * @throws InvalidArgumentException
   * @return string
   */
  public static function getIcon( $type )
  {
    if ( isset( static::$icon[$type] ) ) {
      return static::$icon[$type];
    } else {
      throw new InvalidArgumentException("Icon for type '$type' does not exist.");
    }
  }

  /**
   * Return the data of a node of the tree as expected by the qooxdoo tree data model
   * @param string $datasource Datasource name
   * @param \app\models\Folder|int $folder
   * @return array
   * @todo remove integer folderId, datasource
   * @throws \yii\db\Exception
   */
  public function getNodeData( $datasource, $folder )
  {
    if ( ! ($folder instanceof Folder ) ){
      assert( \is_numeric( $folder) );
      $folderId = $folder;
      // @todo this works only if included in the folder controller
      $folder = $this->getControlledModel($datasource)::findOne($folder);
      if( ! $folder ){
        throw new InvalidArgumentException("Folder #$folderId does not exist.");
      }
    }
    return static::getNodeDataStatic($folder);
  }

  /**
   * Return the data of a node of the tree as expected by the qooxdoo tree data model
   * @param Folder $folder
   * @return array
   * @todo remove integer folderId, datasource
   * @throws \yii\db\Exception
   */
  static public function getNodeDataStatic( Folder $folder )
  {
    $folderType     = $folder->type;
    $owner          = $folder->owner;
    $label          = $folder->label;
    $query          = $folder->query;
    $searchFolder   = (bool) $folder->searchfolder;
    $description    = $folder->description;
    $parentId       = $folder->parentId;

    $childCount     = $folder->getChildCount();
    $referenceCount = (int) $folder->getReferences()->count();
    $markedDeleted  = $folder->markedDeleted;
    $public         = $folder->public;
    $opened         = $folder->opened;

    // access
    static $activeUser = null;
    static $isAnonymous = null;
    if ( $activeUser === null )
    {
      $activeUser = Yii::$app->user->getIdentity();
      $isAnonymous = $activeUser->isAnonymous();
    }

    if ( $isAnonymous ) {
      // do not show unpublished folders to anonymous roles
      if ( ! $public )  return null;
    }

    // reference count is zero if folder executes a query
    if ( $query ) {
      $referenceCount = "";
    }

    // icon & type
    $icon = static::$icon["closed"];
    $type = "folder";
    if (( $folderType === "search" or !$folderType or $searchFolder) and $query  ) {
      $icon = static::$icon["search"];
      $type = "search";
    } elseif ( $folderType == "trash" ) {
      $icon = static::$icon["trash"];
      $type = "trash";
    } elseif ( $folderType == 'favorites' ) {
      $icon = static::$icon["favorites"];
      $type = "favorites";
    } elseif ( $markedDeleted ) {
      $icon = static::$icon['marked-deleted'];
    } elseif ( $public ) {
      $icon = static::$icon['public'];
    }

    // return node data
    $data = [
      'isBranch'        => true,
      'label'           => $label,
      'bOpened'         => $opened,
      'icon'            => $icon,
      'iconSelected'    => $icon,
      'bHideOpenClose'  => ($childCount == 0),
      'columnData'      => [ null, $referenceCount ],
      'data'            => [
        'type'            => $type,
        'id'              => $folder->id,
        'parentId'        => $parentId,
        'query'           => $query,
        'public'          => $public,
        'owner'           => $owner,
        'description'     => $description,
        'datasource'      => get_class($folder)::getDatasource()->namedId,
        'childCount'      => $childCount,
        'referenceCount'  => $referenceCount,
        'markedDeleted'   => $markedDeleted
      ]
    ];
    return $data;
  }

  /**
   * @param $datasource
   * @param $folder
   * @return array
   * @throws \yii\db\Exception
   */
  public function getUpdateNodeData( $datasource, $folder )
  {
    return [
      "datasource" => $datasource,
      "modelType"  => "folder",
      "nodeData"   => $this->getNodeData( $datasource, $folder)
    ];
  }

  /**
   * Given a folder, returns the query data that would be sent by the client to retrieve
   * reference data
   * @todo This has to be rewritten in its entirety
   * @param Folder $folder
   * @param string $modelType
   * @param array $properties
   * @return object
   */
  public function createClientQueryData(Folder $folder, string $modelType, array $properties=[])
  {
    $clientQueryData = [
      "datasource"  => $folder::getDatasource()->namedId,
      "modelType"   => $modelType,
    ];
    if( $folder->searchfolder && $folder->query !== "" ){
      $clientQueryData['query'] = [
        'properties'  => $properties,
        'cql' => $folder->query
      ];
    } else {
      $clientQueryData['query'] = [
        'properties'  => $properties,
        'relation' => [
          'name'        => 'folders',
          'id'          => $folder->id,
          'foreignId'   => 'FolderId'
        ]
      ];
    }
    return json_decode(json_encode($clientQueryData));
  }
}