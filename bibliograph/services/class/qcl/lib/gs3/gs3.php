<?php
/**
 * Class that is a PHP Stream wrapper 
 *
 *
 *    This class register a stream wapper called "s3".
 *    With this you could write, read, delete files and also
 *    create and delete directories (buckets) as you do with 
 *    your local filesytem.
 *
 *     @category   PHP Stream Wrapper
 *     @category   Web Services
 *     @package    gS3
 *     @author     Cesar D. Rodas <saddor@gmail.com>
 *     @copyright  2007 Cesar D. Rodas
 *     @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 *     @version    1.0
 *     @link       http://cesars.users.phpclasses.org/gs3
 */
$include_dir = dirname( __FILE__ );
require_once($include_dir."/hash.php"); 
require_once($include_dir."/http.php"); 

/**
 *    mkdir: Only the owner could have access
 */
define('_PRIVATE',  1);

/**
 *    mkdir: Only the owner could write, but every one could
 *    read.
 */
define('_PUBLIC_READ',  2);

/**
 *    mkdir: Any one could read or write
 */
define('_PUBLIC_WRITE', 3);

/**
 *    fopen: Read a file.
 */
define('READ', 'r');
/**
 *    fopen: Write a file as private
 */
define('WRITE','w');
/**
 *    fopen: Write a file as Public read.
 */
define('WRITE_PUBLIC','w+r');
/**
 *    fopen: Write a file as Public write.
 */
define('WRITE_PUBLIC_WRITE','w+w');

/**
 *    Transaction Result
 *
 *    This variable is a global var that is store blank
 *    is the transaction was OK or have the error text
 *
 *    @var string
 *    @access public
 */
$amazonResponse;


/**
 *    Simple Storage Service stream wrapper
 *
 *    @access public
 *    @author Cesar D. Rodas <saddor@gmail.com>
 *    @copyright  2007 Cesar D. Rodas
 *    @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 *    @package gS3 
 */
class gs3_IO {
    /**
     *    HTTP Connection class
     *    @var object
     *    @access private 
     **/
    var $http;
    
    /**
     *    True if this class was contructed
     *
     *    @var bool
     *    @access private
     */
    var $contructed;
    /**
     *    The opened URL
     *
     *    @var string
     *    @access private
     */
    var $path;
    /**
     *    Type of open. 
     *    @var bool True for write, false only for read
     *    @access private
     */
    var $tOpen;
    /**
     *    Type of ACL of a opened file for write
     *
     *    @var int 
     *    @access private
     */
    var $tAcl;
    /**
     *    Actual position of the file
     *    @var int
     *    @access private
     */
    var $position;
    /**
     *    In memory file buffer.
     *    
     *    @var string
     *    @access private
     */
    var $buffer;
    /**
     *    Buffer actual size
     *
     *    @var int
     *    @access private
     */
    var $bufSize;
    /**
     *    Buffer Max Size
     *
     *    @var int
     *    @access private
     */
    var $bufActSize;
    /**
     *    Array with list of files
     *
     *    @var array 
     *    @access private
     */
    var $dirList;
    /**
     *    Actual file in the directory
     *    
     *    @var int
     *    @access private
     */
    var $actualDir;
    /**
     *    Actual tag, used in XML parse
     *
     *    @var string
     *    @access private
     */
    var $actualTag;
    /**
     *    Stats Variable
     *
     *    @var array
     *    @access private
     */
    var $stat;
    /** 
     *    Flag for End Of file
     *
     *    @var bool
     *    @access private
     */
    var $isEOF;
    /**
     *    Save the request file path
     *    @access private
     *    @var string
     */
    var $reqFile;
    function gs3_IO() {
        if ($this->contructed) return;
        $http=new http_class;
        $http->timeout=0;
        $http->data_timeout=0;
        $http->debug=0;
        $http->html_debug=0;
        $http->user_agent="Cesar D. Rodas' gS3 Class (+http://cesars.phpclasses.org/gs3)";
        $http->follow_redirect=1;
        $http->redirection_limit=5;
        $http->exclude_address="";
        $http->protocol_version="1.1";
        $this->http = $http;
        $this->contructed = true;
        $this->position=0;
        $this->buffer="";
        
    }
    /**
     *    Open 
     *
     *
     *
     */
    function stream_open($path, $mode, $options, $opened_path) {
        if ($this->getPathNumberOfComponents($path)  != 2) {
            trigger_error("$path is not a valid amazon s3 file path. A file *must* be inside of a bucket",E_USER_NOTICE);
            return false;
        }
        $rmethod='PUT';
        $this->tOpen=true;
        switch($mode) {
            case WRITE:
            case 'wb':
                $acl = _PRIVATE;
                break;
            case WRITE_PUBLIC:
                $acl = _PUBLIC_READ;
                break;
            case WRITE_PUBLIC_WRITE:
                $acl = _PUBLIC_WRITE;
                break;
            case READ:
            case 'rb': /* thanks to Jeff Arthur */
                $rmethod='GET';
                $this->tOpen=false;
                break;
            default:
                trigger_error("$mode is not supported. Visit <a href='http://cesarodas.com/gs3/gS3/_gs3.php.html#defineREAD' target='_blank'>doc</a> for further details",E_USER_NOTICE);
                return false;
        }    
        $this->reqFile = $path;
        $this->initialize($path,$rmethod,$url);
        $http=$this->http;        
        $this->path  = $url; 
        if ($this->tOpen) {
            /* the file was opened for read, so exit, because file is do when the file is closed */
            $this->tAcl = $acl;
             return true;
        }
        /* The file is opened for read. */
        
        $http->GetRequestArguments($url,$arguments);  /* parse arguments */
        $this->getS3AuthCode('GET',$arguments);
        $r = $this->Process($arguments, $headers);
        $this->bufActSize = $headers['content-length'];
        global $content_type;
        $content_type = $headers['content-type'];
        return $r;
    }
    
    /**
     *    Return the actual pointer position
     *    @return int
     */
    function stream_tell() {
        return $this->position;
    }
    
    /**
     *    Set a new position.
     *    @param int $offset Number of bits to move
     *    @param int $whence SEEK_SET, SEEK_CUR or SEEK_END
     *    @return int|bool The new position or false.
     */
    function stream_seek($offset, $whence) {
        $l= $this->bufActSize; 
        $p=$this->position;
        switch ($whence) {
            case SEEK_SET: $newPos = $offset; break;
            case SEEK_CUR: $newPos = $p + $offset; break;
            case SEEK_END: $newPos = $l + $offset; break;
            default: return false;
        }
        $ret = ($newPos >=0 && $newPos <=$l);
        if ($ret) $p=$newPos;
        return $ret;
    }
    
    /**
     *    Write a $data into the buffer.
     *    
     *    @return int|bool Numbe of bytes written or false.
     */     
    function stream_write($data){
        if (!$this->tOpen) return false;
        $v=$this->buffer;
        $l=strlen($data);
        $p=$this->position;
        $v = substr($v, 0, $p) . $data . substr($v, $p += $l);
        return $l;
    }
    
    /**
     *    Read a data from the S3 object
     *
     *    @return string 
     */
    function stream_read($count)
    {
        if ($this->tOpen) return false;
        while (!$this->isEOF && $this->position+$count > $this->bufSize && $this->bufSize != $this->bufActSize) {
            /* the required part is not on the buffer, so download it */
            $err=$this->http->ReadReplyBody($tmp,1024);
            if($err!="" && strlen($tmp)==0) $this->isEOF = true;
            $this->buffer .= $tmp; /* buffer this! */
            $this->bufSize += strlen($tmp);
        } 
        $ret = substr($this->buffer, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    } 
    /**
     *    EOF
     *
     *    implements the eof()
     */
    function stream_eof()
    {
        return $this->isEOF;
    } 
    /**
     *    Close the Connection
     *
     *    Close the connecting, and if the file was opened for write
     *    the file is sended to the S3 server.
     *
     *    @return bool
     */
    function stream_close() {
        $http = $this->http;
        $r = true;
        if ($this->tOpen) {
            $http->GetRequestArguments($this->path,$arguments);  /* parse arguments */
            $arguments["Body"]=$this->buffer;
            $arguments['Headers']['Content-Type'] = isset($content_type) ? $content_type : $this->getMimeOfFileType($this->path);
            $arguments['Headers']['Content-Length'] = strlen( $arguments["Body"] );
            if ($this->tAcl) $arguments['Headers']['x-amz-acl'] = $this->accessId2String( $this->tAcl );
            $this->getS3AuthCode('PUT',$arguments);
            
            $r = $this->Process($arguments, $headers);
        }
        $http->Close();
        return $r;
    }
    /**
     *    Implements the fstats
     *    
     *    Thanks to Jeff Arthur for ask this needed feature
     */
    function stream_stat() {
        return stat( $this->reqFile );
    }
    
    /** 
     *    Implements the Stats
     *
     *    @return array
     */
    function url_stat($path,  $flags) {
        if ($this->getPathNumberOfComponents($path)  != 2) {
            trigger_error("$path is not a valid amazon s3 file path. A file *must* be inside of a bucket",E_USER_NOTICE);
            return false;
        }


        $this->initialize($path,'HEAD',$url);
 
        $http=$this->http;
        $http->GetRequestArguments($url,$arguments);  /* parse arguments */
        $this->getS3AuthCode('HEAD',$arguments);    
        $e=$this->Process($arguments, $headers);

        
        if ($e==true) {
            $e = array('size'=>$headers['content-length'],'mtime'=> strtotime($headers['last-modified']), 'atime' => time() );
        }
        return $e;
    }
    
    /**    
     *    Create a Directory or Bucket
     *
     *    Example of usage:
     *
     *    <code>
     *<?php
     *    include("gs3.php");
     *    define('S3_KEY', '059d545s4d6554'); //fake-code
     *    define('S3_PRIVATE','dsadsadshajkdhas') //fake-code
     *    $e=mkdir("s3://foldername",_PRIVATE||_PUBLIC_READ||_PUBLIC_WRITE);
     *    if ($e) echo "Done";
     *    else echo "Error! Amazon said: ".$amazonResponse;
     *?>
     *    </code>
     *    Nested folders could not be done!, that is a Amazon S3 Limitation
     *
     *    @param    string $name Bucket name
     *    @param  int $mode Permision of the bucket
     *    @return bool true if success
     */
    function mkdir($name, $mode=_PRIVATE) {
        if ($this->getPathNumberOfComponents($name)  != 1) {
            trigger_error("$path is not a valid amazon s3 a bucket",E_USER_NOTICE);
            return false;
        }
        $this->initialize($name,'PUT',$url);
        $http=$this->http;
        /*
         *    Parse the request URL into parts that
         *    the httpclient object could process
         */
        
        $http->GetRequestArguments($url,$arguments); 
        $arguments['Headers']['x-amz-acl'] = $this->accessId2String($mode);
        /*
         *    Now get the S3 Authentication code
         */
        $this->getS3AuthCode('PUT',$arguments);
       
           $r = $this->Process($arguments, $headers);
        $http->Close(); 
        return $r;
    }
    /**
     *    Implements the unlink referece
     *
     */
    function unlink($name) {
        if ($this->getPathNumberOfComponents($name)  != 2) {
            trigger_error("$path is not a valid amazon s3 file path. A file *must* be inside of a bucket",E_USER_NOTICE);
            return false;
        }
        $this->initialize($name,'DELETE',$url);
        $http=$this->http;
        $http->GetRequestArguments($url,$arguments);  /* parse arguments */
        $this->getS3AuthCode('DELETE',$arguments);
    
        
        return $this->Process($arguments, $headers);
    }
    
    
     /**
     *    Implements the unlink referece
     *
     */
    function rmdir($name,$options) {
        if ($this->getPathNumberOfComponents($path)  != 1) {
            trigger_error("$path is not a valid amazon s3 bucket",E_USER_NOTICE);
            return false;
        }
        $this->initialize($name,'DELETE',$url);
        $http=$this->http;
        $http->GetRequestArguments($url,$arguments);  /* parse arguments */
        $this->getS3AuthCode('DELETE',$arguments);
    
        
        return $this->Process($arguments, $headers);
    }
    
    /**
     *    Implementing opendir()
     *
     */
    function dir_opendir($path, $options) {
        $this->actualDir = 0;
        $this->dirList = array();
        
        if ($this->getPathNumberOfComponents($path)  != 1) {
            trigger_error("$path is not a valid amazon s3 bucket",E_USER_NOTICE);
            return false;
        }


        $this->initialize($path,'GET',$url);
        $http=$this->http;
        $http->GetRequestArguments($url,$arguments);  /* parse arguments */
        $this->getS3AuthCode('GET',$arguments);    
        $e=$this->Process($arguments, $headers);
        
        if ($e==true) {
            $response="";
            for(;;)
            {
                $error=$http->ReadReplyBody($body,1000);
                if($error!="" || strlen($body)==0) break;
                $response.=($body);
            }    
            
            $xml = xml_parser_create(); 
            xml_parser_set_option($xml,XML_OPTION_CASE_FOLDING,true);
            xml_set_element_handler($xml, array($this,"_dirStart"),array($this,"_dirEnd") ); 
            xml_set_character_data_handler($xml,array($this,"_dirData") );
            xml_parse($xml,$response, true);
            xml_parser_free($xml);
            
            $http->close();
        }
        return $e;
    }
    /**
     *    Readdir
     *    
     */
    function dir_readdir() {
        return (count($this->dirList) > $this->actualDir) ? $this->dirList[ $this->actualDir++ ] : false;
    }

    /**
     *    Rewind dir
     *
     */    
    function dir_rewinddir() {
        $this->actualDir=0;
        
    }    
    /**
     *    close dir
     *
     */
    function dir_closedir() {
        $this->dir_rewinddir();
        $this->dirList = array();
    }
    
    /**
     *    Handle start of XML tags
     *
     */
    function _dirStart($parser,$name,$attribs){
        $this->actualTag = $name;
    }
    /**
     *    Handle end of XML tags
     *
     */
    function _dirEnd($parser,$name){
        $this->actualTag = "";
    }
    /**
     *    Handle data of XML tags
     *
     *    Save in an array when TAG == "KEY"
     */
    function _dirData($parser,$data){
        if ($this->actualTag=="KEY") $this->dirList[] = $data;
    } 
    
    
    /**
     *    Initialize a the httpclient
     *    @param string $name file name
     *    @param string $rmethod What to do.. PUT, GET, DELETE...
     *    @param string $url By reference function with get the URL 
     *    @access private 
     */
    function initialize($name,$rmethod, $url) {
        /*
         *    Call class contructor
         */
        $this->gs3_IO(); 
        /*
         *    Reference the httpclient object
         */
        $http = $this->http;
        /*
         * The calling method for create something is PUT 
         */
        $http->request_method= $rmethod; 
        /*
         * Now Create the URL for request with PUT
         */
        $name = substr($name,5);
        $url = "http://s3.amazonaws.com/${name}";
    }
    
    /** 
     *    Open connection and ask something
     *
     *    @access private
     *    @param array $arguments 
     *    @param array $gHeaders 
     */
    function Process($arguments,$gHeaders) {
        $http = $this->http;
        /* open */
        $http->Open($arguments); 
        /* send request */
        $http->SendRequest($arguments);
        /* get response headers */
        $http->ReadReplyHeaders($tmp);
        $gHeaders = $tmp;

        /* error check */
        global $amazonResponse;
        $amazonResponse='';
        $http->response_status.=""; //convert to string

        if ($http->response_status[0] != 2) {
            /*Something were wrong*/
            $amazonResponse='';
            for(;;)
            {
                $error=$http->ReadReplyBody($body,1000);
                if($error!="" || strlen($body)==0) break;
                $amazonResponse.=($body);
            }    
            $http->Close();
            return false;
        }
        return true;
    }
    
    
    /** 
     *    Return the Authentication code
     *
     *    @access private
     *    @param string $ReqMethod the kind of Request method (PUT, DELETE, GET, POST)
     *    @param array $args The httpclient arguments   
     */
    function getS3AuthCode($ReqMethod, $args) {
        
        $headers = $args['Headers'];
        $headers['Date'] = gmdate("D, d M Y G:i:s T");
        
        /* 
         * building AUTH code
         */
        $type = isset($headers['Content-Type']) ? $headers['Content-Type'] : "";
        $md5 = isset($headers['Content-MD5'])   ? $headers['Content-MD5'] : "";
        $access = isset($headers['x-amz-acl'])  ? "x-amz-acl:".$headers['x-amz-acl']."\n" : "";
        $stringToSign = $ReqMethod."\n$md5\n$type\n".$headers['Date']."\n".$access;
        $stringToSign.= $args['RequestURI'];
        //die($stringToSign);
        $hasher = new Crypt_HMAC(S3_PRIVATE, "sha1");
        $signature = $this->hex2b64($hasher->hash($stringToSign));
        

        $headers['Authorization'] = " AWS ".S3_KEY.":".$signature;
        
    }
    
    /**
     *    Return the string of the access
     *
     *    @access private
     *    @param int $access Type of access
     *    @return string The string of access
     */
    function accessId2String($access) {
        switch($access) {
            case _PUBLIC_READ:
                $s = "public-read";
                break;
            case _PUBLIC_WRITE:
                $s = "public-read-write";
                break;
            default:
                $s = "private";
        }
        return $s;
    }
    
    /**
     *    Encode a field for amazon auth
     *
     *    @access private
     *    @param string $str String to encode
     *    @return string
     */
    function hex2b64($str) {
        $raw = '';
        for ($i=0; $i < strlen($str); $i+=2) {
            $raw .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($raw);
    } 
    
    /**
     *    Return the "content/type" of a file based on the file name
     *
     *    @param string $name File name
     *    @access private
     *    @return string mime type 
     */
    function getMimeOfFileType($name) {
        switch(is_integer($dot=strrpos($name,".")) ? strtolower(substr($name,$dot)) : "")
        {
            case ".xls":
                $content_type="application/excel";
                break;
            case ".hqx":
                $content_type="application/macbinhex40";
                break;
            case ".doc":
            case ".dot":
            case ".wrd":
                $content_type="application/msword";
                break;
            case ".pdf":
                $content_type="application/pdf";
                break;
            case ".pgp":
                $content_type="application/pgp";
                break;
            case ".ps":
            case ".eps":
            case ".ai":
                $content_type="application/postscript";
                break;
            case ".ppt":
                $content_type="application/powerpoint";
                break;
            case ".rtf":
                $content_type="application/rtf";
                break;
            case ".tgz":
            case ".gtar":
                $content_type="application/x-gtar";
                break;
            case ".gz":
                $content_type="application/x-gzip";
                break;
            case ".php":
            case ".php3":
                $content_type="application/x-httpd-php";
                break;
            case ".js":
                $content_type="application/x-javascript";
                break;
            case ".ppd":
            case ".psd":
                $content_type="application/x-photoshop";
                break;
            case ".swf":
            case ".swc":
            case ".rf":
                $content_type="application/x-shockwave-flash";
                break;
            case ".tar":
                $content_type="application/x-tar";
                break;
            case ".zip":
                $content_type="application/zip";
                break;
            case ".mid":
            case ".midi":
            case ".kar":
                $content_type="audio/midi";
                break;
            case ".mp2":
            case ".mp3":
            case ".mpga":
                $content_type="audio/mpeg";
                break;
            case ".ra":
                $content_type="audio/x-realaudio";
                break;
            case ".wav":
                $content_type="audio/wav";
                break;
            case ".bmp":
                $content_type="image/bitmap";
                break;
            case ".gif":
                $content_type="image/gif";
                break;
            case ".iff":
                $content_type="image/iff";
                break;
            case ".jb2":
                $content_type="image/jb2";
                break;
            case ".jpg":
            case ".jpe":
            case ".jpeg":
                $content_type="image/jpeg";
                break;
            case ".jpx":
                $content_type="image/jpx";
                break;
            case ".png":
                $content_type="image/png";
                break;
            case ".tif":
            case ".tiff":
                $content_type="image/tiff";
                break;
            case ".wbmp":
                $content_type="image/vnd.wap.wbmp";
                break;
            case ".xbm":
                $content_type="image/xbm";
                break;
            case ".css":
                $content_type="text/css";
                break;
            case ".txt":
                $content_type="text/plain";
                break;
            case ".htm":
            case ".html":
                $content_type="text/html";
                break;
            case ".xml":
                $content_type="text/xml";
                break;
            case ".mpg":
            case ".mpe":
            case ".mpeg":
                $content_type="video/mpeg";
                break;
            case ".qt":
            case ".mov":
                $content_type="video/quicktime";
                break;
            case ".avi":
                $content_type="video/x-ms-video";
                break;
            case ".eml":
                $content_type="message/rfc822";
                break;
            default:
                $content_type="application/octet-stream";
                break;
        }
        return $content_type;
    }
    
    /**
     *    Get the number of components of a path
     *    
     *    Example:
     *    s3://path/cesar = 2    
     *    s3://path/      = 1
     *
     *    @param string $path Path
     *    @return int
     *    @access private
     */
    function getPathNumberOfComponents($path) {
        $p = explode("/",substr($path,5, strlen($path)-6));
        return count($p);
    }
}

stream_wrapper_register("s3","gs3_IO") or trigger_error("Failed to register protocol gS3");
