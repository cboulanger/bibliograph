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

qcl_import( "qcl_data_model_db_TreeNodeModel" );

/**
 * Default folder model
 */
class bibliograph_model_FolderModel
  extends qcl_data_model_db_TreeNodeModel
{
  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  protected $tableName = "data_Folder";

  private $properties = array(

    'type'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(20)"
    ),
    'description'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    ),
    'searchable'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'init'      => true
    ),
    'searchfolder'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'init'      => false
    ),
    'query'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(255)"
    ),
    'public'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'init'      => false
    ),
    'opened'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'init'      => false
    ),
    'locked'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'init'      => false
    ),
    'path'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    ),
    'owner'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(30)"
    ),
    'hidden'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'init'      => false
    ),
    'createdBy'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(20)"
    ),
    'markedDeleted'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'init'      => false
    ),
    'childCount'  => array(
      'check'       => "integer",
      'sqltype'     => "INT(11)",
      'nullable'    => true,
      'init'        => 0
    ),
    'referenceCount'  => array(
      'check'       => "integer",
      'sqltype'     => "INT(11)"
    ),
  );

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "FolderId";

  /**
   * Relations
   */
  private $relations = array(
    'Folder_Reference' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "bibliograph_model_ReferenceModel" )
    )
  );


  //-------------------------------------------------------------
  // Class properties
  //-------------------------------------------------------------

  //-------------------------------------------------------------
  // Init
  //-------------------------------------------------------------

  function __construct( $datasourceModel )
  {
    /*
     * when the model is first used and the data tables are set up,
     * add the initial folders
     */
    parent::__construct( $datasourceModel );

    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
    $this->addFormData( $datasourceModel );

    $this->init();

    if( $this->countRecords() == 0 )
    {
      $this->addInitialFolders();
    }
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
//        ,'events'    => array(
//          'focus'    =>  "function(e){
//                            var hwin = this.getApplication().getWidgetById('searchHelpWindow');
//                            this.getApplication().setInsertTarget(e.getTarget());
//                            hwin.setZIndex(2147483647);
//                            hwin.set({maxWidth:250});
//                            hwin.moveTo(10,10);
//                            hwin.show();
//
//                          }"
//          ,'blur'    =>  "function(){
//                            var hwin = this.getApplication().getWidgetById('searchHelpWindow');
//                            hwin.setMaxWidth(400);
//                            hwin.hide();
//                          }"
//        )
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
  // API
  //-------------------------------------------------------------


  /**
   * Overridden to cache child count
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


  //-------------------------------------------------------------
  // Public API
  //-------------------------------------------------------------

  /**
   * Adds basic folders
   * @return void
   */
  function addInitialFolders()
  {
    $this->log( "Adding initial folders to $this", BIBLIOGRAPH_LOG_APPLICATION );

    // top folder
    $this->create(array(
      "label"       => $this->tr("Default Folder"),
      "parentId"    => 0,
      "position"    => 0,
      "childCount"  => 0,
      "public"      => true
    ));

    // trash folder
    $this->create(array(
      "type"        => "trash",
      "label"       => $this->tr("Trash Folder"),
      "parentId"    => 0,
      "position"    => 1,
      "childCount"  => 0,
      "public"      => false
    ));
  }
}
