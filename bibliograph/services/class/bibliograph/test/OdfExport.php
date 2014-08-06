<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_test_AbstractTestController" );

class class_bibliograph_test_OdfExport
  extends qcl_test_AbstractTestController
{

  public function method_testOdfExport()
  {
    /** @noinspection PhpIncludeInspection */
    require_once('bibliograph/lib/odtphp/odf.php');

    $filepath = qcl_realpath("bibliograph/test/test.odt");
    $odf = new odf( $filepath );

    $odf->setVars('date', date("d.m.Y") );

    $data = array(
      array(
        'name'    => 'John Doe',
        'address' => '1234 54th St W,Farmville, MN 34563,U.S.A'
      ),
      array(
        'name'    => 'Mary Miller',
        'address' => '32 Anderson Avenue,Farmville, MN 34563,U.S.A'
      ),
      array(
        'name'    => 'Betty Baker',
        'address' => '21 Kingston Road,Farmville, MN 34563,U.S.A'
      ),

    );

    $letter = $odf->setSegment('letters');
    foreach($data AS $element)
    {
      $letter->setVars( "name",    $element['name'] );
      $letter->setVars( "address", $element['address'] );
      $letter->merge();
    }

    $odf->mergeSegment( $letter );

    $odf->exportAsAttachedFile("mailmerge.odt");
    exit;
  }
}
