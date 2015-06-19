<?php
/* ************************************************************************

   qcl - the qooxdoo component library

   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_Result");
qcl_import( "qcl_test_TestRunner");

class qcl_test_data_TestResult
  extends qcl_data_Result
{
  var $a;
  var $b;
  var $c;
}

class qcl_test_data_ControllerModel
  extends qcl_test_TestRunner
{

  function test_testRecord()
  {
    $record = array(
      'a' => "Foo",
      'b' => "Boo",
      'c' => "Hoo"
    );
    $result = new qcl_test_data_TestResult;
    return $result->set($record);
  }

  function test_testResultSet()
  {
    $i = "a";
    $testMap = array(
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
      array( 'a' => $i++, 'b' => $i++, 'c' => $i++),
    );

    $result = new qcl_test_data_TestResult;
    return $result->queryResultToModel($testMap);
  }
}
