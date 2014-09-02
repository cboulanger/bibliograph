<?php
///* ************************************************************************
//
//   Bibliograph: Collaborative Online Reference Management
//
//   http://www.bibliograph.org
//
//   Copyright:
//     2004-2014 Christian Boulanger
//
//   License:
//     LGPL: http://www.gnu.org/licenses/lgpl.html
//     EPL: http://www.eclipse.org/org/documents/epl-v10.php
//     See the LICENSE file in the project's top-level directory for details.
//
//   Authors:
//   *  Christian Boulanger (cboulanger)
//
//************************************************************************ */
//

throw new qcl_core_NotImplementedException("z3950_Schema");
//
//qcl_import( "bibliograph_schema_BibtexSchema" );
//
///**
// * Class containing data on the BibTex Format
// */
//class z3950_Schema
//  extends bibliograph_schema_BibtexSchema
//{
//
//  /*
//        <field name="1" property="author">
//          <label>Personal name</label>
//        </field>
//        <field name="2" property="author">
//          <label>Corporate name</label>
//        </field>
//        <field name="3" property="booktitle">
//          <label>Conference name</label>
//        </field>
//        <field name="4" property="title">
//          <label>Title</label>
//        </field>
//        <field name="5" property="series">
//          <label>Title series</label>
//        </field>
//        <field name="6" property="title">
//          <label>Title uniform</label>
//        </field>
//        <field name="7" property="isbn">
//          <label>ISBN</label>
//        </field>
//        <field name="8" property="issn">
//          <label>ISSN</label>
//        </field>
//        <field name="9" property="lccn">
//          <label>LC card number</label>
//        </field>
//        <field name="12" property="lccn">
//          <label>Local number</label>
//        </field>
//        <field name="13" property="lccn">>
//          <label>Dewey classificat</label>
//        </field>
//        <field name="14" property="lccn">>
//          <label>UDC classification</label>
//        </field>
//        <field name="15" property="lccn">>
//          <label>Bliss classification</label>
//        </field>
//        <field name="16" property="lccn">>
//          <label>LC call number</label>
//        </field>
//        <field name="17" property="lccn">>
//          <label>NLM call number</label>
//        </field>
//        <field name="18" property="lccn">>
//          <label>NAL call number</label>
//        </field>
//        <field name="19" property="lccn">>
//          <label>MOS call number</label>
//        </field>
//        <field name="20" property="lccn">>
//          <label>Local classification</label>
//        </field>
//        <field name="21" property="keywords">>
//          <label>Subject heading</label>
//        </field>
//        <field name="22" property="keywords">
//          <label>Subject Rameau</label>
//        </field>
//        <field name="23" property="keywords">
//          <label>BDI index subject</label>
//        </field>
//        <field name="24" property="keywords">
//          <label>INSPEC subject</label>
//        </field>
//        <field name="25" property="keywords">
//          <label>MESH subject</label>
//        </field>
//        <field name="26" property="keywords">
//          <label>PA subject</label>
//        </field>
//        <field name="27" property="keywords">
//          <label>LC subject heading</label>
//        </field>
//        <field name="28" property="keywords">
//          <label>RVM subject heading</label>
//        </field>
//        <field name="29" property="keywords">
//          <label>Local subject index</label>
//        </field>
//        <field name="30" property="year">
//          <label>Date</label>
//        </field>
//        <field name="31" property="keywords">
//          <label>Date of publication</label>
//        </field>
//        <field name="32" property="note">
//          <label>Date of acquisition</label>
//        </field>
//        <field name="33" property="note">
//          <label>Title key</label>
//        </field>
//        <field name="34" property="note">
//          <label>Title collective</label>
//        </field>
//        <field name="35" property="note">
//          <label>Title parallel</label>
//        </field>
//        <field name="36" property="note">
//          <label>Title cover</label>
//        </field>
//        <field name="37" property="none">
//          <label>Title added title page</label>
//        </field>
//        <field name="38" property="none">
//          <label>Title caption</label>
//        </field>
//        <field name="39" property="none">
//          <label>Title running</label>
//        </field>
//        <field name="40" property="none">
//          <label>Title spine</label>
//        </field>
//        <field name="41" property="none">
//          <label>Title other variant</label>
//        </field>
//        <field name="42" property="none">
//          <label>Title former</label>
//        </field>
//        <field name="43" property="none">
//          <label>Title abbreviated</label>
//        </field>
//        <field name="44" property="none">
//          <label>Title expanded</label>
//        </field>
//        <field name="45" property="keywords">
//          <label>Subject precis</label>
//        </field>
//        <field name="46" property="keywords">
//          <label>Subject rswk</label>
//        </field>
//        <field name="47" property="keywords">
//          <label>Subject subdivision</label>
//        </field>
//        <field name="48" property="none">
//          <label>No. nat'l biblio.</label>
//        </field>
//        <field name="49" property="none">
//          <label>No. legal deposit</label>
//        </field>
//        <field name="50" property="none">
//          <label>No. govt pub.</label>
//        </field>
//        <field name="51" property="none">
//          <label>No. music publisher</label>
//        </field>
//        <field name="52" property="none">
//          <label>Number db</label>
//        </field>
//        <field name="53" property="none">
//          <label>Number local call</label>
//        </field>
//        <field name="54" property="none">
//          <label>Code--language</label>
//        </field>
//        <field name="55" property="none">
//          <label>Code--geographic area</label>
//        </field>
//        <field name="56" property="none">
//          <label>Code--institution</label>
//        </field>
//        <field name="57" property="none">
//          <label>Name and title</label>
//        </field>
//        <field name="58" property="none">
//          <label>Name geographic</label>
//        </field>
//        <field name="59" property="address">
//          <label>Place publication</label>
//        </field>
//        <field name="60" property="none">
//          <label>CODEN</label>
//        </field>
//        <field name="61" property="none">
//          <label>Microform generation</label>
//        </field>
//        <field name="62" property="abstract">
//          <label>Abstract</label>
//        </field>
//        <field name="63" property="note">
//          <label>Note</label>
//        </field>
//        <field name="1000" property="none">
//          <label>Author-title</label>
//        </field>
//        <field name="1001" property="note">
//          <label>Record type</label>
//        </field>
//        <field name="1002" property="author">
//          <label>Name</label>
//        </field>
//        <field name="1003" property="author">
//          <label>Author</label>
//        </field>
//        <field name="1004" property="author">
//          <label>Author-name personal</label>
//        </field>
//        <field name="1005" property="author">
//          <label>Author-name corporate</label>
//        </field>
//        <field name="1006" property="none">
//          <label>Author-name conference</label>
//        </field>
//        <field name="1007" property="none">
//          <label>Identifier--standard</label>
//        </field>
//        <field name="1008" property="keywords">
//          <label>Subject--LC children's</label>
//        </field>
//        <field name="1009" property="keywords">
//          <label>Subject name -- personal</label>
//        </field>
//        <field name="1010" property="note">
//          <label>Body of text</label>
//        </field>
//        <field name="1011" property="none">
//          <label>Date/time added to db</label>
//        </field>
//        <field name="1012" property="none">
//          <label>Date/time last modified</label>
//        </field>
//        <field name="1013" property="none">
//          <label>Authority/format id</label>
//        </field>
//        <field name="1014" property="none">
//          <label>Concept-text</label>
//        </field>
//        <field name="1015" property="none">
//          <label>Concept-reference</label>
//        </field>
//        <field name="1016" fulltext="true" searchField="true">
//          <label>Any</label>
//        </field>
//        <field name="1017" property="none">
//          <label>Server-choice</label>
//        </field>
//        <field name="1018" property="publisher">
//          <label>Publisher</label>
//        </field>
//        <field name="1019" property="none">
//          <label>Record-source</label>
//        </field>
//        <field name="1020" property="editor">
//          <label>Editor</label>
//        </field>
//        <field name="1021" property="none">
//          <label>Bib-level</label>
//        </field>
//        <field name="1022" property="none">
//          <label>Geographic-class</label>
//        </field>
//        <field name="1023" property="none">
//          <label>Indexed-by</label>
//        </field>
//        <field name="1024" property="none">
//          <label>Map-scale</label>
//        </field>
//        <field name="1025" property="none">
//          <label>Music-key</label>
//        </field>
//        <field name="1026" property="none">
//          <label>Related-periodical</label>
//        </field>
//        <field name="1027" property="none">
//          <label>Report-number</label>
//        </field>
//        <field name="1028" property="none">
//          <label>Stock-number</label>
//        </field>
//        <field name="1030" property="none">
//          <label>Thematic-number</label>
//        </field>
//        <field name="1031" property="none">
//          <label>Material-type</label>
//        </field>
//        <field name="1032" property="none">
//          <label>Doc-id</label>
//        </field>
//        <field name="1033" property="none">
//          <label>Host-item</label>
//        </field>
//        <field name="1034" property="none">
//          <label>Content-type</label>
//        </field>
//        <field name="1035" fulltext="true" searchField="true">
//          <label>Anywhere</label>
//        </field>
//        <field name="1036" fulltext="true" searchField="true">
//          <label>Author-Title-Subject</label>
//        </field>
//   */
//
//
//
//  /**
//   * @return lsbaer_ReferenceModel
//   */
//  static public function getInstance()
//  {
//    return qcl_getInstance( __CLASS__ );
//  }
//
//  /**
//   * Constructor
//   */
//  function __construct()
//  {
//
//    $this->addFields( array(
//      'raum' => array(
//        'label'     => "Raum",
//        'type'      => "string",
//        'formData'  => array(
//          'label'     => "Raum",
//          'type'      => "combobox",
//          'bindStore' => array(
//            'serviceName'   => "bibliograph.reference",
//            'serviceMethod' => "getUniqueValueListData",
//            'params'        => array( '$datasource', "raum" )
//          ),
//          'autocomplete'  => array(
//            'enabled'   => true,
//            'separator' => null
//           )
//        )
//      ),
//      'kategorie' => array(
//        'label'     => "Kategorie",
//        'type'      => "string",
//        'public'    => false,
//        'formData'  => array(
//          'label'     => "Kategorie",
//          'type'      => "combobox",
//          'bindStore' => array(
//            'serviceName'   => "bibliograph.reference",
//            'serviceMethod' => "getUniqueValueListData",
//            'params'        => array( '$datasource', "kategorie" )
//          ),
//          'autocomplete'  => array(
//            'enabled'   => true,
//            'separator' => null
//           )
//        )
//      ),
//      'besitzerin' => array(
//        'label'     => "Besitzerin",
//        'type'      => "string",
//        'public'    => false,
//        'formData'  => array(
//          'label'     => "Besitzerin",
//          'type'      => "combobox",
//          'bindStore' => array(
//            'serviceName'   => "bibliograph.reference",
//            'serviceMethod' => "getUniqueValueListData",
//            'params'        => array( '$datasource', "besitzerin" )
//          ),
//          'autocomplete'  => array(
//            'enabled'   => true,
//            'separator' => null
//          )
//        )
//      )
//
//    ));
//
//    $this->addToTypeFields( array(
//      'raum','kategorie','besitzerin'
//    ));
//  }
//}
//?>