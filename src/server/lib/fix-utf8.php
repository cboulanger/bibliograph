<?php
require __DIR__ . '/../vendor/neitanod/forceutf8/src/ForceUTF8/Encoding.php';

function fix_double_encoding($string)
{
  $utf8_chars = explode(' ', 'À Á Â Ã Ä Å Æ Ç È É Ê Ë Ì Í Î Ï Ð Ñ Ò Ó Ô Õ Ö × Ø Ù Ú Û Ü Ý Þ ß à á â ã ä å æ ç è é ê ë ì í î ï ð ñ ò ó ô õ ö');
  $utf8_double_encoded = array();
  foreach ($utf8_chars as $utf8_char) {
    $utf8_double_encoded[] = utf8_encode(utf8_encode($utf8_char));
  }
  $string = str_replace($utf8_double_encoded, $utf8_chars, $string);
  return $string;
}

echo ForceUTF8\Encoding::fixUTF8(fix_double_encoding(stream_get_contents(STDIN)));
