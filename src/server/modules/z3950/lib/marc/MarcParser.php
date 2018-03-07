<?php

class MarcParser
{
  /**
   * converts marc-8 data to utf-8 using the fastest available
   * method
   * @param string  $marc8  string or file name with marc-8 data (xml or text)
   */
  function toUtf8($marc8)
  {
    // use php for the moment
    return $this->_toUtf8Php($marc8);
  }

  /**
   * Converts MARC8 data to utf-8 using a (slow) php function
   * @param string  $marc8 MARC-8 encoded string or file
   * @return string
   */
  function _toUtf8Php($marc8)
  {

    /*
     * get file content if neccessary
     */
    if ( @is_file($marc8) )
    {
      $string = file_get_contents($marc8);
    }
    else
    {
      $string = $marc8;
    }

    /*
     * parse helper map into static variable
     */
    static $ans2uniMap = null;

    if ( ! is_object( $ans2uniMap ) )
    {

      foreach( file( Z3950_ANS2UNI_DAT_PATH ) as $line )
      {
        $line   = explode("#",$line);
        $line   = array_shift($line); // get rid of comments
        $line   = explode("=",$line);
        $target = array_pop($line); // get target character
        $seq  = explode("+",$line[0]); // get character sequence

        if( count($seq)==1 and hexdec($seq[0]) != hexdec($target) )
        {
          $ans2uniMap[hexdec($seq[0])][0] = hexdec($target);
        }
        if(count($seq)==2)
        {
          $ans2uniMap[hexdec($seq[0])][hexdec($seq[1])][0] = hexdec($target);
        }
        if(count($seq)==3)
        {
          $ans2uniMap[hexdec($seq[0])][hexdec($seq[1])][hexdec($seq[2])][0] = hexdec($target);
        }
      }
    }

    /*
     * convert ansi data
     */
    $i = 0;
    $output = "";

    while( $i<strlen( $string ) )
    {
      $c0 = ord($string{$i});
      $c1 = ord($string{$i+1});
      $c2 = ord($string{$i+2});
      $unicode = false;

      if($ans2uniMap[$c0][$c1][$c2][0])
      {
        $unicode = $ans2uniMap[$c0][$c1][$c2][0];
        $i+=3;
      }
      elseif ( $ans2uniMap[$c0][$c1][0] )
      {
        $unicode = $ans2uniMap[$c0][$c1][0];
        $i+=2;
      }
      elseif ( $ans2uniMap[$c0][0] )
      {
        $unicode = $ans2uniMap[$c0][0];
        $i+=1;
      }
      else
      {
        $output .= $string{$i++};
      }

      /*
       * the following code snippet is from Marc Grimshaw's "Charmyknife"
       */
      if($unicode)
      {
        $utf8 = '';
        if ($unicode < 128)
        {
          $utf8 .= chr($unicode);
        }
        elseif($unicode < 2048)
        {
          $utf8 .= chr(192 + (($unicode - ($unicode % 64)) / 64));
          $utf8 .= chr(128 + ($unicode % 64));
        }
        else
        {
          $utf8 .= chr( 224 + (($unicode - ($unicode % 4096)) / 4096));
          $utf8 .= chr( 128 + ((($unicode % 4096) - ($unicode % 64)) / 64));
          $utf8 .= chr( 128 + ($unicode % 64));
        }
        $output .= $utf8;
      }
    }

    /*
     * strip remaining invalid characters
     * @todo
     */
    $output =  qcl_util_encoding_Converter::convert("utf-8","utf-8",$output);

    return $output;
  }
}
