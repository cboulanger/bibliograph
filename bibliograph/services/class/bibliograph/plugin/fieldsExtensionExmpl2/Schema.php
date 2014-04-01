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
class bibliograph_plugin_fieldsExtensionExmpl2_Schema
  extends bibliograph_schema_BibtexSchema
{


  /**
   * @return bibliograph_plugin_fieldsExtensionExmpl2_ReferenceModel
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
       * Typ der Publikation (z.B. Kommentar, Gesetz, etc.
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
        ),
        'csl'       => false,
        'index'     =>  _("Type")
      ),

      /*
       * Standort des Werks
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
        ),
        'csl'       => false,
        'index'     => _("Location")
      ),

      /*
       * Thematische Kategorie
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
        ),
        'csl'       => false,
        'index'     => _("Category")
      ),

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
        ),
        'csl'       => false,
        'index'     =>  _("Owner")

      ),

      '_source' => array(
        'label'     => _("Source"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Source"),
          'type'      => "combobox",
          'bindStore' => array(
            'serviceName'   => "bibliograph.reference",
            'serviceMethod' => "getUniqueValueListData",
            'params'        => array( '$datasource', "_source" )
          ),
          'autocomplete'  => array(
            'enabled'   => true,
            'separator' => null
           )
        ),
        'csl'       => false,
        'index'     => _("Source")
      ),

      '_date_ordered' => array(
        'label'     => _("Date ordered"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Date ordered"),
          'type'      => "datefield"
         )
       ),
      '_date_received' => array(
        'label'     => _("Date received"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Date received"),
          'type'      => "datefield"
         )
       ),
      '_date_reimbursement_requested' => array(
        'label'     => _("Reimb. requested"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Reimb. requested"),
          'type'      => "datefield"
         )
       ),

       '_inventory'  => array(
        'label'     => _("Inventory"),
        'type'      => "string",
        'public'    => false,
        'formData'  => array(
          'label'     => _("Inventory"),
          'type'      => "combobox",
          'bindStore' => array(
            'serviceName'   => "bibliograph.reference",
            'serviceMethod' => "getUniqueValueListData",
            'params'        => array( '$datasource', "_inventory" )
          )
        )
      ),
     )
   );

    $this->addToTypeFields( array(
      'type','location','_category','_owner','price','_source',
      '_date_ordered','_date_received','_date_reimbursement_requested',
      '_inventory'
    ));
  }
}
?>