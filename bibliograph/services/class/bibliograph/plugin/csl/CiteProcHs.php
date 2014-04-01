<?php
/**
 * Citation styling using CSL and citeproc-hs
 *
 * Dependencies:
 * pandoc/citeproc-hs (http://code.haskell.org/citeproc-hs/)
 * hs-bibutils (http://code.haskell.org/hs-bibutils/) 
 * 
 * @author Christian Boulanger (c dot boulanger at qxtransformer dot org)
 * @author ... Please join the fun!
 * 
 */

/*
 * directory with executables
 */
if ( ! defined("CITEPROC_EXEC_DIR") )
{
  define("CITEPROC_EXEC_DIR", "/usr/local/bin" );
}


/*
 * name of pandoc executable (Might have to be pandoc.exe on windows?)
 */
if ( ! defined("CITEPROC_PANDOC_BIN") )
{
  define("CITEPROC_PANDOC_BIN", "pandoc" );
}

/**
 * CiteProc class
 *
 * @author Christian Boulanger (c.boulanger at qxtransformer dot org)
 *
 */
class CiteProc
{

  /**
   * The URL or the remote repository
   */
  var $remoteRepositoryUrl = "http://www.zotero.org/styles";
  
  /**
   * The last shell command executed. 
   */
  var $cmd;
  
  /**
   * Constructor (PHP4/PHP5)
   */
  function CiteProc()
  {
    /*
     * check executable directory
     */
    if ( ! is_dir( CITEPROC_EXEC_DIR ) )
    {
      trigger_error( "Please define CITEPROC_EXEC_DIR constant to point at the directory where pandoc is located.");
    }

  }

  /**
   * Returns the path of the directory that contains the style files
   * @return string
   */
  function getStyleDir()
  {
    return dirname(__FILE__) . "/styles/";
  }

  /**
   * Returns an array of localy cached styles
   * @return array Array of arrays ( shortname, description )
   */
  function getStyles()
  {
    $styleDir = $this->getStyleDir();
    $styles   = array();
    foreach( scandir( $styleDir ) as $file )
    {
      if ( $file[0] == "." ) continue;

      $styleXml = $styleDir . "/" . $file;

      $name = str_replace(".csl","",$file);

      $content = file_get_contents( $styleXml );
      if ( preg_match("/<title>(.*)<\/title>/",$content,$matches) )
      {
        $title = $matches[1];
      }
      else
      {
        $title = $name;
      }

      $styles[] = array( $name, $title, "file://$styleXml" );
    }
    return $styles;
  }

  /**
   * Returns the URL where the styles are stored. 
   * @return string
   */
  function getRemoteStylesUrl()
  {
    return $this->remoteRepositoryUrl;
  }

  /**
   * Returns the URL of a particular style
   */
  function getRemoteStyleUrl( $name )
  {
    return $this->getRemoteStylesUrl() . "/$name";
  }

  /**
   * Returns a list of styles that are in the remote repository
   */
  function getRemoteStyles()
  {
    $url = $this->getRemoteStylesUrl();

    if ( $this->check_connection( $url ) )
    {
      /*
       * download styles from zotero.org
       */
      $html = file_get_contents($url);
    }
    else
    {
      trigger_error("Cannot connect to zotero.org");
    }

    $pattern = '/href="\/styles\/([^"?]+)">([^<]+)<\/a>/';
    
    preg_match_all($pattern, $html, $matches );
    $styles = array();
    foreach ( $matches[1] as $index => $name )
    {
      $styles[] = array( $name, $matches[2][$index], "$url/$name" );
    }
    return $styles;
  }

  /**
   * Downloads a style from the remote repository to the local
   * style cache
   * @param string $name
   * @return string
   */
  function downloadStyle( $name )
  {
    $url = $this->getRemoteStyleUrl( $name );
    $xml = file_get_contents( $url );
    $this->saveStyle( $name, $xml );
    return $xml;
  }

  /**
   * Save a style
   * @param $name
   * @param $xml
   * @return bool Sucess
   */
  function saveStyle( $name, $xml )
  {
    if ( ! $name or ! $xml )
    {
      trigger_error("Invalid arguments.");
    }

    $styleDir = $this->getStyleDir();
    if ( ! is_writeable( $styleDir ) )
    {
      trigger_error("Style directory is not writable");
    }
    return file_put_contents( $styleDir . str_replace("/","_",$name) . ".csl", $xml );
  }

  /**
   * Processes incoming data to styled bibliography.
   * @param $biblio The bibliographic data
   * @param $text Text containing citations
   * @param $biblioFormat
   * @param $csl
   * @throws InvalidArgumentException
   * @return string
   */
  function process( $biblio, $text, $biblioFormat, $csl )
  {
    /*
     * style file
     */
    $cslFile = realpath( $this->getStyleDir() . str_replace("/","_",$csl) . ".csl" );
    if ( ! file_exists( $cslFile ) )
    {
      throw new InvalidArgumentException("Style '$csl' does not exist.");
    }
     
    /*
     * bibliographic data
     */
    $biblioFile = realpath( tempnam(null,null) );
    file_put_contents( $biblioFile, $biblio );

    /*
     * citations
     */
    $textFile = realpath( tempnam(null, null) );
    file_put_contents( $textFile, $text );

    /*
     * modify path for binary call
     * @todo windows!
     */
    putenv("PATH=" . $_SERVER["PATH"] . ":" . CITEPROC_EXEC_DIR );
    putenv("DYLD_LIBRARY_PATH=''" ); // Mac MAMP only

    /*
     * call citeproc-hs via pandoc
     */
    $this->cmd =
      CITEPROC_PANDOC_BIN .
      " --csl $cslFile" .
      " --biblio $biblioFile" .
      " --biblio-format $biblioFormat" . 
      " $textFile" . 
      " 2>&1";
      
    $output = shell_exec( $this->cmd );

    /*
     * delete temporary files
     */
    unlink( $textFile );
    unlink( $biblioFile );

    return $output;
  }

  /**
   * Check to see if we can open a connection to the remote repository
   */
  function check_connection( $url=null )
  {
    if ( ! $url ) $url = $this->getRemoteStylesUrl();
    $skt = @fsockopen( $url, 80);
    if ($skt)
    {
      fclose($skt);
      return true;
    }
    return false;
  }
}

/*
 * end here if PHP5
 */
if ( phpversion()>=5 ) return;

/**
 * PHP4 file_put_contents function
 * @param string $file
 * @param string $data
 */
if ( ! function_exists("file_put_contents") )
{
  function file_put_contents($file,$data)
  {
    @unlink($file);
    error_log($data,3,$file);
    return file_exists($file);
  }
}
/**
 * PHP4 scandir function
 * from http://www.php.net/manual/en/function.scandir.php
 * @return array list of files
 * @param string $dir
 * @param boolean $sortorder
 */
if ( ! function_exists("file_put_contents") )
{
  function scandir($dir, $sortorder = 0)
  {
    if(is_dir($dir) && $dirlist = @opendir($dir))
    {
      while(($file = readdir($dirlist)) !== false)
      {
        $files[] = $file;
      }
      closedir($dirlist);
      ($sortorder == 0) ? asort($files) : rsort($files); // arsort was replaced with rsort
      return $files;
    }
    else
    {
      return false;
    }
  }
}
?>