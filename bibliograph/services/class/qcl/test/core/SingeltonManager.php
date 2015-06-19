<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
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

class qcl_test_core_SingetonClassA extends qcl_core_Object {};
class qcl_test_core_SingetonClassB extends qcl_core_Object {};
class qcl_test_core_SingetonClassC extends qcl_core_Object {};

class qcl_test_core_SingletonManager extends qcl_test_TestRunner
{
  public static function test_createSingleton()
  {
    $a1 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassA");
    $a2 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassA");
    assert('$a1 === $a2', "Objects are not identical!");
  }
  
  public static function test_resetSingleton()
  {
    $a1 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassA");
    qcl_core_SingletonManager::resetInstance("qcl_test_core_SingetonClassA");
    $a2 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassA");
    assert('$a1 !== $a2', "Objects should not be identical!");
  }
  
  public static function test_resetSingletonWithRegExpr()
  {
    $a1 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassA");
    $b1 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassB");
    $c1 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassC");
    
    qcl_core_SingletonManager::resetInstance("/qcl_test_core_SingetonClass*/", true);
    
    $a2 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassA");
    $b2 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassB");
    $c2 = qcl_core_SingletonManager::createInstance("qcl_test_core_SingetonClassC");
    
    assert('$a1 !== $a2 and $b1 !== $b2 and $c1 !== $c2', "Objects should not be identical!");
  }
}

qcl_test_core_SingletonManager::getInstance()->run();