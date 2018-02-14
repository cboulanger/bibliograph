<?php
// This returns the content of bibliograph.ini.php as an array, with ${...} macros expanded
// @todo move into @lib
$ini = parse_ini_file ( APP_INI_FILE, true, INI_SCANNER_TYPED );
// replace macros
array_walk_recursive( $ini, 
function( &$value ) use($ini) {
  if( ! is_string( $value ) ) return;
  $value = trim( preg_replace_callback('/\$\{([^}]+)\}/', 
  function( $matches ) use($ini) {
    $parts = explode(".",$matches[1]);
    $v = $ini;
    while( is_array($v) and $part = array_shift($parts) ){
      if ( isset( $v[$part] ) ) $v = $v[$part];
      else return null;
    }
    return $v;
  }, $value ) );
});
return $ini;