<?php 
/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2014 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */
qcl_import("qcl_data_store_IKeyValueStore");

class qcl_data_store_keyvalue_Session
  implements qcl_data_store_IKeyValueStore
{
  public function has( $key )
  {
    qcl_assert_valid_string( $key );
    return isset($_SESSION[__CLASS__][$key]);
  }
  
  public function get( $key )
  {
    qcl_assert_valid_string( $key );
    return $_SESSION[__CLASS__][$key];
  }  
  
  public function set( $key, $value )
  {
    qcl_assert_valid_string( $key );
    $_SESSION[__CLASS__][$key] = $value;
  }  
  
  public function delete( $key )
  {
    qcl_assert_valid_string( $key );
    unset($_SESSION[__CLASS__][$key]);
  }
}
?>