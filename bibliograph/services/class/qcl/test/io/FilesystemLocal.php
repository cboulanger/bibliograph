<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */
 
require_once dirname(__DIR__)."/bootstrap.php";

qcl_import("qcl_test_TestRunner");
qcl_import("qcl_test_TestRunner");
qcl_import("qcl_io_filesystem_local_File");
qcl_import("qcl_io_filesystem_local_Folder");

/**
 * Service class containing test methods
 */
class qcl_io_filesystem_local_Tests
  extends qcl_test_TestRunner
{

  function test_testCreate()
  {
    $topDir = new qcl_io_filesystem_local_Folder( "file://" . QCL_VAR_DIR . "/qcl-test" );
    $topDir->create();
    
    $file1  = $topDir->createOrGetFile("file1");
    $file2  = $topDir->createOrGetFile("file2");
    $dir1   = $topDir->createOrGetFolder("dir1");
    $dir1->create();

    $file3 = $dir1->createOrGetFile("file3");
    //$file3->rename("file3b");

    $file4 = $dir1->createOrGetFile("file4");
    $file4->delete();
    
  }

  function test_testDirContents()
  {
    $topDir = new qcl_io_filesystem_local_Folder( "file://" . QCL_VAR_DIR . "/qcl-test" );
    $topDir->open();
    while ( $resource = $topDir->next() )
    {
      $this->info( $resource->basename() . ": " . $resource->className() );
    }
    $topDir->close();
  }

  function test_testAnalysePath()
  {
    $dirObj = new qcl_io_filesystem_local_Folder( "file://" . QCL_VAR_DIR . "/qcl-test" );
    $this->info ( "Dirname:   " . $dirObj->dirname() );
    $this->info ( "Basename:  " . $dirObj->basename() );
    $this->info ( "Extension: " . $dirObj->extension() );
    $this->info ( "Is File? " . ( $dirObj->isFile() ? "Yes." : "No.") );
    $this->info ( "Is Dir? " .  ( $dirObj->isDir() ? "Yes." : "No.") );

    $fileObj = new qcl_io_filesystem_local_File( "file://" . QCL_VAR_DIR . "test123.txt" );

    $this->info ( "Dirname:   " . $fileObj->dirname() );
    $this->info ( "Basename:  " . $fileObj->basename() );
    $this->info ( "Extension: " . $fileObj->extension() );
    $this->info ( "Is File? " . ( $fileObj->isFile() ? "Yes." : "No.") );
    $this->info ( "Is Dir? " . (  $fileObj->isDir() ? "Yes." : "No.") );
  }
}

//qcl_io_filesystem_local_Tests::getInstance()->run();