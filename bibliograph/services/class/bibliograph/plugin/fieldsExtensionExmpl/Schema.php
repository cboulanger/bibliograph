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

qcl_import( "bibliograph_schema_BibtexSchema" );

/**
 * Class containing data on the BibTex Format
 */
class bibliograph_plugin_fieldsExtensionExmpl_Schema
  extends bibliograph_schema_BibtexSchema
{


  /**
   * @return bibliograph_plugin_fieldsExtensionExmpl_ReferenceModel
   */
  static public function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Constructor
   */
  function __construct()
  {
    parent::__construct();

    $this->addFields( array(
      /*
       * The type of publication
       */
      'type' => array(
        'label'     => _("Type"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Type"),
          'type'      => "combobox",
          'bindStore' => array(
            'serviceName'   => "bibliograph.reference",
            'serviceMethod' => "getUniqueValueListData",
            'params'        => array( '$datasource', "type" )
          ),
          'autocomplete'  => array(
            'enabled'   => true,
            'separator' => null
          )
        )
      ),

      /*
       * Where it is stored
       */
      'location' => array(
        'label'     => _("location"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("location"),
          'type'      => "combobox",
          'bindStore' => array(
            'serviceName'   => "bibliograph.reference",
            'serviceMethod' => "getUniqueValueListData",
            'params'        => array( '$datasource', "location" )
          ),
          'autocomplete'  => array(
            'enabled'   => true,
            'separator' => null
          )
        )
      ),

      /*
       * A thematic category
       */
      '_category' => array(
        'label'     => _("Category"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Category"),
          'type'      => "combobox",
          'bindStore' => array(
            'serviceName'   => "bibliograph.reference",
            'serviceMethod' => "getUniqueValueListData",
            'params'        => array( '$datasource', "_category" )
          ),
          'autocomplete'  => array(
            'enabled'   => true,
            'separator' => null
           )
        )
      ),

      /*
       * who owns it
       */
      '_owner' => array(
        'label'     => _("Owner"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Owner"),
          'type'      => "combobox",
          'bindStore' => array(
            'serviceName'   => "bibliograph.reference",
            'serviceMethod' => "getUniqueValueListData",
            'params'        => array( '$datasource', "_owner" )
          ),
          'autocomplete'  => array(
            'enabled'   => true,
            'separator' => null
          )
        )
      ),

      /*
       * When was it ordered
       */
      '_date_ordered' => array(
        'label'     => _("Date ordered"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Date ordered"),
          'type'      => "datefield"
         )
       ),

       /*
        * when was it received
        */
      '_date_received' => array(
        'label'     => _("Date received"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Date received"),
          'type'      => "datefield"
         )
       )
     )
   );

    $this->addToTypeFields( array(
      'type','location','_category','_owner',
      'price',
      '_date_ordered','_date_received'
    ));
  }
}
