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
     * Christian Boulanger (cboulanger)

   This code contains documentation taken from
   http://www.php.net/manual/en/book.yaz.php
   Creative Commons Attribution 3.0 License,
   copyright (c) the PHP Documentation Group, but probably
   authored by IndexData, see http://www.indexdata.com/yaz

************************************************************************ */

class YAZException extends Exception {}

class YAZ
{

  /**
   * Resource pointer
   * @var resource
   */
  protected $resource;

  /**
   * The url of th z39.50 database
   * @var string
   */
  protected $zurl;

  /**
   * An array of options for the connection
   * @var string
   */
  protected $options = array();

  /**
   * An array mapping BIB-1 index numbers to the titles of the
   * fields and other information on the index
   * @var array
   */
  protected $indexes = array();

  /**
   * The available syntaxes offered by the database
   * @var unknown_type
   */
  protected $syntax = array();
  
  /**
   * Constructor.
   * @param $zurl
   *  A string that either is a URL to a Z39.50 server or the path to a XML
   *  EXPLAIN document that contains the necessary connection parameters.
   *  If the argument is a URL, it takes the form host[:port][/database]. If port is omitted,
   *  port 210 is used. If database is omitted Default is used.
   *  if the argument is a file, the form is protocol://path/to/explain.xml
   *  Any path that is supported by the stream wrapper of PHP (local filesystem,
   *  remote webpage, etc.), as long as the path ends with ".xml"
   *
   * @param $options
   *  If given as a string, it is treated as the Z39.50 V2 authentication string
   *  (OpenAuth).
   *  If given as an array, the contents of the array serves as options, overriding
   *  settings in a explain file set by the first argument.
   *
   *    user        Username for authentication.
   *
   *    group       Group for authentication.
   *
   *    password    Password for authentication.
   *
   *    cookie      Cookie for session (YAZ proxy).
   *
   *    proxy       Proxy for connection (YAZ proxy).
   *
   *    persistent  A boolean. If TRUE the connection is persistent;
   *                If FALSE the connection is not persistent. By default
   *                connections are persistent. Note: If you open a persistent
   *                connection, you won't be able to close it later with close().
   *
   *    piggyback   A boolean. If TRUE piggyback is enabled for searches;
   *                If FALSE piggyback is disabled. By default piggyback is
   *                enabled. Enabling piggyback is more efficient and usually
   *                saves a network-round-trip for first time fetches of records.
   *                However, a few Z39.50 servers do not support piggyback or
   *                they ignore element set names. For those, piggyback should
   *                be disabled.
   *
   *    charset     A string that specifies character set to be used in Z39.50
   *                language and character set negotiation. Use strings such as:
   *                ISO-8859-1, UTF-8, UTF-16. Most Z39.50 servers do not support
   *                this feature (and thus, this is  ignored). Many servers use
   *                the ISO-8859-1 encoding for queries and messages.
   *                MARC21/USMARC records are not affected by this setting.
   *
   *    preferredMessageSize An integer that specifies the maximum byte size of
   *                all records to be returned by a target during retrieval. See
   *                the � Z39.50 standard for more information.
   *
   *    maximumRecordSize An integer that specifies the maximum byte size of a
   *                single record to be returned by a target during retrieval.
   *                This entity is referred to as Exceptional-record-size in the
   *                 � Z39.50 standard.
   *
   * @return void
   */
  public function __construct( $zurl, $options=array() )
  {

    if( substr( $zurl, -4 ) == ".xml" )
    {
      $this->parseExplainDoc( $zurl );
    }
    else
    {
      $this->zurl = $zurl;
    }

    if( is_array( $options ) )
    {
      foreach( $options as $key => $value )
      {
        $this->options[$key] = $value;
      }
    }
    elseif ( is_string( $options ) )
    {
      $this->options = $options;
    }
    else
    {
      throw new InvalidArgumentException("Invalid options argument");
    }
  }

  /**
   * Parses the content of an xml explain document
   * @param string $path Must be the path to an existing valid
   * xml document that is sent as a response to a Z39.50 Explain request.
   * path can be local or remote as long as it is supported by PHP's
   * stream wrappers
   *
   * @return void
   */
  protected function parseExplainDoc( $path )
  {
    $doc = file_get_contents( $path );
    $explain = simplexml_load_string( $doc );

    /*
     * server info
     */
    $serverInfo = $explain->serverInfo;
    $host = (string) $serverInfo->host;
    $port = (string) $serverInfo->port;
    $database = (string) $serverInfo->database;
    if( $port )
    {
      $this->zurl = "$host:$port/$database";
    }
    else
    {
      $this->zurl = "$host/$database";
    }

    // encoding of the databaser
    $this->options['charset'] = either ( 
      (string) $serverInfo->charset, 
      (string) $serverInfo->encoding,
      "marc-8"
    ); 
    
    /*
     * non-standard
     */
    if ( $serverInfo->authentication )
    {
      $this->options['user'] = (string) $serverInfo->authentication->user;
      $this->options['password'] = (string) $serverInfo->authentication->password;
    }

    /*
     * database info
     */
    $databaseInfo = $explain->databaseInfo;
    if( $databaseInfo )
    {
      $this->databaseInfo = array(
        'title'   => (string) $databaseInfo->title,
        'author'  => (string) $databaseInfo->author,
        'contact' => (string) $databaseInfo->contact
      );
    }

    /*
     * index info
     */
    $indexInfo = $explain->indexInfo;
    foreach( $indexInfo->children() as $index )
    {
      $search = ( (string) $index["search"] == "true" );
      $title  = (string) $index->title;
      $lang   = (string) $index->title['lang'];
      $attr   = $index->map->attr;
      //$type   = (string) $attr['type'];//ignored for the moment
      //$set    = (string) $attr['set']; //ignored for the moment
      $attr   = (string) $attr;

      $this->indexes[$attr] = array(
        'title'   => $title,
        'lang'    => $lang,
        //'type'    => $type,
        //'set'     => $set,
        'search'  => $search
      );
    }

    /*
     * record info
     */
    $recordInfo = $explain->recordInfo;
    foreach( $recordInfo->children() as $recordSyntax )
    {
      $this->syntax[] = array(
        'name'        => (string) $recordSyntax['name'],
        'elementset'  => (string) $recordSyntax->elementSet['name']
      );
    }
  }

  /**
   * Throws an exception after a failed operation
   * @param string $message Optional additional message. Otherwise the
   * yaz error message will be printed;
   * @throws YAZException
   */
  public function throwException( $message="" )
  {
    $message .=
      ( empty( $message ) ? "" : ": " ) .
      yaz_error( $this->resource ) . " - ".
      yaz_addinfo( $this->resource );

    throw new YAZException( $message, yaz_errno( $this->resource) );
  }
  
  
  /**
   * Checks for a YAZ error and throws an exception with a 
   * descriptive error message
   */
  protected function checkError()
  {
    if ( $error = yaz_error( $this->resource ) )
    {
      $this->throwException($error);
    }
  }
  
  /**
   * Return the last yaz error, if any
   */
  public function getError()
  {
    return yaz_error( $this->resource );
  }

  /**
   * Returns the url of the Z39.50 server.
   * @return string
   */
  public function getZUrl()
  {
    return $this->zurl;
  }

  /**
   * Get a list of available syntax identifiers as an array
   * @return array
   */
  public function  getSyntaxList()
  {
    return $this->syntax;
  }

  /**
   * Get the available bib-1 indexes
   * @return array
   */
  public function getIndexes()
  {
    return $this->indexes;
  }

  /**
   * This function configures the CCL query parser for a server with
   * definitions of access points (CCL qualifiers) and their mapping to
   * RPN. To map a specific CCL query to RPN afterwards call the
   * ccl_parse() function.
   * @param $config An array of configuration. Each key of the array is the
   * name of a CCL field and the corresponding value holds a string that
   * specifies a mapping to RPN.The mapping is a sequence of attribute-type,
   * attribute-value pairs. Attribute-type and attribute-value is separated
   * by an equal sign (=). Each pair is separated by white space. Example:
   *
   * array(
   *   "ti"   => "1=4",
   *   "au"   => "1=1",
   *   "isbn" => "1=7"
   * );
   *
   * @see http://www.indexdata.com/yaz/doc/tools.html#CCL
   * @return void
   */
  public function ccl_configure( $config )
  {
    yaz_ccl_conf( $this->resource, $config );
  }

  /**
   * This function invokes a CCL parser. It converts a given CCL FIND query to
   * an RPN query which may be passed to the yaz_search() function to perform a
   * search. To define a set of valid CCL fields call ccl_conf() prior to
   * this function.
   * @param string $query
   *    The CCL FIND query.
   *
   * @return string
   *    The rpn query string
   */
  public function ccl_parse( $query )
  {
    /** @var $result array */
    if ( ! yaz_ccl_parse( $this->resource, $query, $result ) )
    {
      throw new YAZException(
        "Ccl parsing failed: " . $result["errorstring"] . " at position " . $result["errorpos"]
      );
    }
    return $result["rpn"];
  }

  /**
   * Closes the connection.
   * Note: This function will only close a non-persistent connection opened
   * by setting the persistent option to FALSE
   * @return void
   */
  public function close()
  {
    yaz_close( $this->resource );
  }

  /**
   * Prepares for a connection to a Z39.50 server. This function is
   * non-blocking and does not attempt to establish a connection -
   * it merely prepares a connect to be performed later when wait()
   * is called.
   *
   * @return void
   */
  public function connect()
  {
    $this->resource = yaz_connect( $this->zurl, $this->options );
    if ( ! $this->resource )
    {
      $this->throwException("Cannot create resource");
    }
    $this->checkError();
  }

  /**
   * This function allows you to change databases within a session
   * by specifying one or more databases to be used in search, retrieval,
   * etc. - overriding databases specified in the constructor.
   *
   * @param string $database
   * @return void
   */
  public function setDatabase( $database )
  {
    if ( ! yaz_database( $this->resource, $database ) )
    {
      $this->throwException("Could not change database");
    }
  }

  /**
   * This function sets the element set name for retrieval.
   * Call this function before search() or present()
   * to specify the element set name for records to be retrieved.
   *
   * @param string $elementset
   *    Most servers support F (for full records) and B (for brief records).
   *
   * @return void
   */
  public function setElementSet( $elementset )
  {
    yaz_element( $this->resource, $elementset );
    return;
    /*if ( ! yaz_element( $this->resource, $elementset ) )
    {
      $this->throwException("Could not set element set");
    }*/
  }

  /**
   * This function inspects the last returned Extended Service result from a
   * server. An Extended Service is initiated by either setItemOrder()
   * or extendedServices().
   *
   * @return array
   *    Returns array with element targetReference for the reference
   *    for the extended service operation (generated and returned from the
   *    server).
   */
  public function extendedServicesResult()
  {
    return yaz_es_result( $this->result );
  }

  /**
   * This function prepares for an Extended Service Request. Extended
   * Services is family of various Z39.50 facilities, such as Record Update,
   * Item Order, Database administration etc.
   *
   * @param string $type
   *    A string which represents the type of the Extended Service:
   *    itemorder (Item Order),
   *    create (Create Database),
   *    drop (Drop Database),
   *    commit (Commit Operation),
   *    update (Update Record),
   *    xmlupdate (XML Update).
   *    Each type is specified in the following section.
   *
   * @param array $args
   *    An array with extended service options plus package specific options.
   *    The options are identical to those offered in the C API of ZOOM C.
   *    Refer to the ZOOM � Extended Services.
   *    http://www.indexdata.com/yaz/doc/zoom.html
   *
   * @return void
   */
  public function extendedServices( $type, $args )
  {
    yaz_es( $this->resource, $type, $args);
  }

  /**
   * Returns the value of the option specified with name.
   * @param $name The option name.
   * @return string
   *    Returns the value of the specified option or an empty string
   *    if the option wasn't set
   */
  public function getOption( $name )
  {
    return yaz_get_option( $this->resource, $name );
  }

  /**
   * Returns the number of hits for the last search.
   * @param $searchresult
   *    Result array for detailed search result information.
   * @return int
   *    Returns the number of hits for the last search or 0 if no search
   *    was performed.
   *
   *    The search result array (if supplied) holds information
   *    that is returned by a Z39.50 server in the SearchResult-1 format part
   *    of a search response. The SearchResult-1 format can be used to obtain
   *    information about hit counts for various parts of the query (subquery).
   *    In particular, it is possible to obtain hit counts for the individual
   *    search terms in a query. Information for first subquery is in
   *    $array[0], second subquery in $array[1], and so forth.
   *
   *    searchresult members  Element Description
   *    id                    Sub query ID2 (string)
   *    count                 Result count / hits (integer)
   *    subquery.term         Sub query term (string)
   *    interpretation.term   Interpretated sub query term (string)
   *    recommendation.term   Recommended sub query term (string)
   */
  public function hits( &$searchresult=array() )
  {
    return yaz_hits( $this->resource, $searchresult );
  }

  /**
   * Prepares for Z39.50 Item Order with an ILL-Request package.
   * This method prepares for an Extended Services request using the Profile
   * for the Use of Z39.50 Item Order Extended Service to  Transport ILL
   * (Profile/1).
   * @see http://www.php.net/manual/en/function.yaz-itemorder.php
   * @param array $args
   *    Must be an associative array with information about the Item Order
   *    request to be sent. The key of the hash is the name of the corresponding
   *    ASN.1 tag path. For example, the ISBN below the Item-ID has the key
   *    item-id,ISBN.
   * @return void
   */
  public function setItemOrder( $args )
  {
    yaz_item_order( $this->resource, $args );
  }

  /**
   * Prepares for retrieval (Z39.50 present).
   * This function prepares for retrieval of records after a successful search.
   * The range() function should be called prior to this function to
   * specify the range of records to be retrieved.
   * @return void
   */
  public function present()
  {
    if ( ! yaz_present( $this->resource ) )
    {
      $this->throwException("PRESENT failed");
    }
  }

  /**
   * Specifies a range of records to retrieve. This function should be called
   * before search() or present().
   * @param int $start
   *    Specifies the position of the first record to be retrieved.
   *    The records numbers goes from 1 to hits().
   *
   * @param int $number
   *    Specifies the number of records to be retrieved.
   *
   * @return void
   */
  public function setRange( $start, $number )
  {
    yaz_range( $this->resource, $start, $number );
  }

  /**
   * Returns a record. Inspects a record in the current result set
   * at the position specified by parameter pos.
   *
   * @param int $pos
   *    The record position. Records positions in a result set are
   *    numbered 1, 2, ... $hits where $hits is the count returned by hits().
   *
   * @param string $type
   *    The type specifies the form of the returned record.
   *    Note: It is the application which is responsible for actually ensuring
   *    that the records are returned from the Z39.50/SRW server in the proper
   *    format. The type given only specifies a conversion to take place on the
   *    client side (in PHP/YAZ). Besides conversion of the transfer record to
   *    a string/array, PHP/YAZ it is also possible to perform a character
   *    set conversion of the record. Especially for USMARC/MARC21 that is
   *    recommended since these are typically returned in the character set
   *    MARC-8 that is not supported by browsers, etc. To specify a conversion,
   *    add ; charset=from, to where from is the original character set of the
   *    record and to is the resulting character set (as seen by PHP).
   *
   *       string
   *
   *       The record is returned as a string for simple display. In this
   *       mode, all MARC records are converted to a line-by-line format
   *       since ISO2709 is hardly readable. XML records and SUTRS are returned
   *       in their original format. GRS-1 are returned in a (ugly) line-by-line
   *       format. This format is suitable if records are to be displayed
   *       in a quick way - for debugging - or because it is not feasible to
   *       perform proper display.
   *
   *       xml
   *
   *       The record is returned as an XML string if possible. In this mode,
   *       all MARC records are converted to � MARCXML. XML records and SUTRS
   *       are returned in their original format. GRS-1 is not supported.
   *       This format is similar to string except that MARC records are
   *       converted to MARCXML This format is suitable if records are processed
   *       by an XML parser or XSLT processor afterwards.
   *
   *       raw
   *
   *       The record is returned as a string in its original form. This type
   *       is suitable for MARC, XML and SUTRS. It does not work for GRS-1.
   *       MARC records are returned as a ISO2709 string. XML and SUTRS are
   *       returned as strings.
   *
   *       syntax
   *
   *       The syntax of the record is returned as a string, i.e. USmarc,
   *       GRS-1, XML, etc.
   *
   *       database
   *
   *       The name of database associated with record at the position is
   *       returned as a string.
   *
   *       array
   *       The record is returned as an array that reflects the GRS-1 structure.
   *       This type is suitable for MARC and GRS-1. XML, SUTRS are not
   *       supported and if the actual record is XML or SUTRS an empty string
   *       will be returned. The array returned consists of a list corresponding
   *       to each leaf/internal node of GRS-1. Each list item consists a sub
   *       list with first element path and data (if data is available).
   *       The path which is a string holds a list of each tree component
   *       (of the structured GRS-1 record) from root to leaf. Each component
   *       is a tag type, tag value pair of the form (type, value String tags
   *       normally has a corresponding tag type 3. MARC can also be returned
   *       as an array (they are converted to GRS-1 internally).
   *
   * @return mixed
   *    Returns the record at position pos or an empty string if no record #
   *    exists at the given position. If no database record exists at the
   *    given position an empty string is returned.
   */
  public function getRecord( $pos, $type )
  {
    return yaz_record( $this->resource, $pos, $type );
  }

  /**
   * Returns Scan Response result, i.e. terms and associated information as
   * received from the server in the last performed scan().
   *
   * @param array $result
   *    If given, this array will be modified to hold additional information
   *    taken from the Scan Response:
   *        number - Number of entries returned
   *        stepsize - Step size
   *        position - Position of term
   *        status - Scan status
   *
   * @return unknown_type
   */
  public function scanResult( &$result )
  {
    if ( yaz_error( $this->resource ) )
    {
      $this->throwException("SCAN failed");
    }
    yaz_scan_result(  $this->resource, $result );
  }

  /**
   * Prepares for a scan. This function prepares for a Z39.50 Scan Request on
   * the specified connection. To actually transfer the Scan Request to the
   * server and receive the Scan Response, wait() must be called. Upon
   * completion of wait() call scan_result() to handle the response.
   *
   *
   * @param string $startterm
   *    Starting term point for the scan.
   *    The form in which the starting term is specified is given by parameter
   *    type. The syntax of this parameter is similar to the RPN query as
   *    described in search(). It consists of zero or more @attr-operator
   *    specifications, then followed by exactly one token.
   *
   * @param array $flags
   *    This optional parameter specifies additional information to control
   *    the behaviour of the scan request. Three indexes are currently read
   *    from the flags array:
   *        number (number of terms requested),
   *        position (preferred position of term) and
   *        stepSize (preferred step size).
   *
   * @param string $type
   *    Currently only type rpn is supported. Defaults to rpn, so no need
   *    to pass the argument
   *
   * @return void
   */
  public function scan( $startterm, $flags, $type="rpn" )
  {
    yaz_scan( $this->resource, $type, $startterm, $flags );
  }

  /**
   * Specifies schema for retrieval.This function should be called before
   * calling search() or present().
   *
   * @param string $schema
   *    Must be specified as an OID (Object Identifier) in a raw dot-notation
   *    (like 1.2.840.10003.13.4) or as one of the known registered schemas:
   *    GILS-schema, Holdings, Zthes, ...
   *
   * @return void
   */
  public function setSchema( $schema )
  {
    yaz_schema( $this->resource, $schema );
  }

  /**
   * Prepares for a search on the given connection. Like connect() this function
   * is non-blocking and only prepares for a search to be executed later when
   * wait() is called.
   *
   * @param YAZ_Query|string $query
   *    The argument can either be an instance of the YAZ_Query class and subclasses
   *    or a string, mostly in the RPN (reverse polish notation) format.
   *
   *    The YAZ_Query object takes care of converting the particular query format
   *    into a valid RPN format.
   *
   *    The string-type RPN query is a textual representation of the Type-1 query as defined
   *    by the Z39.50 standard. However, in the text representation as used by
   *    YAZ a prefix notation is used, that is the operator precedes the
   *    operands. The query string is a sequence of tokens where white space
   *    is ignored unless surrounded by double quotes. Tokens beginning with
   *    an at-character (@) are considered operators, otherwise they are
   *    treated as search terms. If you would like to use a more friendly notation,
   *    use the CCL parser - methods ccl_conf() and ccl_parse().
   *
   * @param string $type
   *    This parameter represents the query type - generally "rpn" is supported now
   *    in which case the first argument specifies a Type-1 query in prefix
   *    query notation. Update: When querying a SRU/SRW server, the "type" parameter
   *    must/can? be specified as "cql". Defaults to "rpn"
   *
   * @see http://www.php.net/manual/en/function.yaz-search.php
   * @see http://www.loc.gov/z3950/agency/defns/bib1.html

   * @return void
   */
  public function search( $query, $type="rpn" )
  {
    if ( $query instanceof YAZ_Query )
    {
      if( $type == "rpn" )
      {
        $query = $query->toRpn( $this );
      }
//      elseif ( $type == "cql" )
//      {
//        $query = $query->toCql( $this );
//      }
      else
      {
        throw new InvalidArgumentException("Invalid type");
      }
    }
    if ( ! yaz_search( $this->resource, $type, $query ) )
    {
      $this->throwException("SEARCH failed");
    }
    $this->checkError();
  }

  /**
   * Sets one or more options on the current connection.
   *
   * @param $first
   *    May be either a string or an array.
   *    If given as a string, this will be the name of the option to set.
   *    You'll need to give it's value.
   *    If given as an array, this will be an associative array
   *    (option name => option value).
   *
   *    PHP/YAZ Connection Options
   *
   *    Name                    Description
   *    ================================================================
   *    implementationName      implementation name of server
   *
   *    implementationVersion   implementation version of server
   *
   *    implementationId        implementation ID of server
   *
   *    schema                  schema for retrieval. By default,
   *                            no schema is used. Setting this option
   *                            is equivalent to using method setSchema()
   *
   *    preferredRecordSyntax   record syntax for retrieval. By default,
   *                            no syntax is used. Setting this option is
   *                            equivalent to using function setSyntax()
   *
   *
   *    start                   offset for first record to be retrieved via
   *                            search() or present(). First record is numbered
   *                            has a start value of 0. Second record has start
   *                            value 1. Setting this option in combination
   *                            with option count has the same effect as
   *                            calling range() except that records are
   *                            numbered from 1 in range()
   *
   *    count                   maximum number of records to be retrieved
   *                            via search() or present().
   *
   *    elementSetName          element-set-name for retrieval. Setting this
   *                            option is equivalent to calling setElement().
   * @param mixed $value
   *    The new value of the option. Use this only if the previous argument
   *    is a string.
   *
   * @return void
   */
  public function setOption( $first, $value=null )
  {
    yaz_set_option( $this->resource, $first, $value );
  }

  /**
   * This function sets sorting criteria and enables Z39.50 Sort.
   * Call this function before search(). Using this function alone does not
   * have any effect. When used in conjunction with search(), a Z39.50 Sort
   * will be sent after a search response has been received and before any
   * records are retrieved with Z39.50 Present ( present()).
   * @param string $criteria
   *    A string that takes the form field1 flags1 field2 flags2 where
   *    field1 specifies the primary attributes for sort, field2 seconds, etc..
   *    The field specifies either a numerical attribute combinations
   *    consisting of type=value pairs separated by comma (e.g. 1=4,2=1) ;
   *    or the field may specify a plain string criteria (e.g. title. The
   *    flags is a sequence of the following characters which may not be
   *    separated by any white space.
   *    Sort Flags:
   *    a Sort ascending
   *    d Sort descending
   *    i Case insensitive sorting
   *    s Case sensitive sorting
   *
   * @return void
   */
  public function sort( $criteria )
  {
    yaz_sort( $this->resource, $criteria );
  }

  /**
   * Specifies the preferred record syntax for retrieval. This function should
   * be called before search() or present().
   *
   * @param string $syntax
   *  The syntax must be specified as an OID (Object Identifier) in a
   *  raw dot-notation (like 1.2.840.10003.5.10) or as one of the known
   *  registered record syntaxes (sutrs, usmarc, grs1, xml, etc.).
   *
   * @return void
   */
  public function setSyntax( $syntax )
  {
    yaz_syntax( $this->resource, $syntax );
  }

  /**
   * Given a list of syntax identifiers, set the first syntax on this list
   * that is available on the current server and return it. If none of the
   * given syntaxes is available, raise a YAZ_Exception. Comparison is
   * case-insensitive and matches can be partial, e.g. "marc" matches
   * "USMarc" and "UKMarc" etc.
   * @throws YAZ_Exception
   * @param array $syntaxList
   * @return string syntax identifier
   */
  public function setPreferredSyntax( array $syntaxList )
  {
    foreach( $syntaxList as $syntax )
    {
      foreach( $this->getSyntaxList() as $syntaxOption )
      {
        if( stripos( $syntaxOption['name'], $syntax ) !== false )
        {
          $this->setSyntax( $syntaxOption['name'] );
          $this->setElementSet( $syntaxOption['elementset'] );
          return $syntaxOption['name'];
        }
      }
    }
    throw new YAZException("$syntax is not available.");
  }

  /**
   * Wait for Z39.50 requests to complete.
   * A static method that carries out networked (blocked) activity for outstanding
   * requests which have been prepared by the functions connect(), search(),
   * present(), scan() and setItemOrder() in all of the instances of this class.
   * wait() returns when all servers have either completed all requests or
   * aborted (in case of errors).
   *
   * @param $options
   *    An associative array of options:
   *    timeout   Sets timeout in seconds. If a server has not responded
   *              within the timeout it is considered dead and wait() returns.
   *              The default value for timeout is 15 seconds.
   *    event     A boolean. In event mode, returns resource
   *
   * @return mixed
   *    Returns TRUE on success or FALSE on failure. In event mode, returns
   *    resource or FALSE on failure.
   */
  static public function wait( $options=array() )
  {
    return yaz_wait( $options );
  }

}

abstract class YAZ_Query
{
  /**
   * The type of the query
   * @var unknown_type
   */
  protected $type;

  /**
   * The query string
   * @var unknown_type
   */
  protected $query;

  /**
   * Constructor
   * @param $type
   * @param $query
   * @return unknown_type
   */
  public function __construct( $query )
  {
    if ( ! $this->type )
    {
      throw new LogicException("Child class must define the type property!");
    }
    $this->query = $query;
  }

  /**
   * Getter for type
   * @return unknown_type
   */
  public function getType()
  {
    return $this->type;
  }

  abstract public function toRpn( YAZ $yaz );

}

class YAZ_RpnQuery extends YAZ_Query
{
  protected $type = "rpn";

  public function toRpn( YAZ $yaz )
  {
    return $this->query;
  }
}

class YAZ_CclQuery extends YAZ_Query
{
  protected $type = "ccl";

  public function toRpn( YAZ $yaz )
  {
    return $yaz->ccl_parse( $this->query );
  }
}

abstract class YAZ_Result
{
  /**
   * The YAZ object
   * @var YAZ
   */
  protected $yaz;
  
  
  /**
   * The record type used by yaz_record
   * @see http://php.net/manual/de/function.yaz-record.php
   * @var string
   */
   protected $type;

  /**
   * Constructor
   * @param YAZ $yaz
   * @param string $type Optional record type
   */
  public function __construct( YAZ $yaz, $type=null )
  {
    $this->yaz  = $yaz;
    if( $type) $this->type = $type;
  }

  /**
   * Retrieves the record at the given position in the given format
   * @param $position
   * @return mixed
   */
  abstract public function addRecord( $position );
}

/**
 * YAZ XML Result
 */
class YAZ_XmlResult extends YAZ_Result
{
  
  protected $xml = "";

  protected $rootStartTag = "<xml>";

  protected $rootEndTag = "</xml>";

  public function getXml()
  {
    $xml  = '<?xml version="1.0" encoding="UTF-8" ?>';
    $xml .= $this->rootStartTag ."\n" .
            $this->xml . "\n" .
            $this->rootEndTag;
    return $xml;
  }

  /**
   * makes a transformation
   */
  public function transform($xsl_filename, $xml )
  {
    $xsltp = new XSLTProcessor();
    $xsldoc = new DOMDocument();
    $xsldoc->load( $xsl_filename );
    $xsltp->importStyleSheet($xsldoc);
    $xmldoc = new DOMDocument();
    $xmldoc->loadXML( $xml );
    return $xsltp->transformToXML($xmldoc);
  }

  /**
   * add the record at the given index position and return it
   * @param int $position
   */
  public function addRecord( $position )
  {
    static $format = null;
    if( $formal === null )
    {
      switch( $charset = $this->yaz->getOption("charset") )
      {
        case "utf-8": 
          $format = "xml"; break;
        default: 
          $format = "xml; charset=$charset,utf-8"; break;
          throw new YAZException("Unknown charset '$charset'.");
      }
    }
    
    $record = $this->yaz->getRecord( $position, $format );
    
    if ( $record ) 
    {
      $this->xml .= $record;
    }
    elseif ( $this->yaz->getError() )
    {
      $this->yaz->throwException("Error retrieving record #$position");
    }
    
    return $record;
  }
}


class YAZ_MarcXmlResult extends YAZ_XmlResult
{

  protected $rootStartTag = '<collection xmlns="http://www.loc.gov/MARC21/slim">';

  protected $rootEndTag = '</collection>';

  public function toDublinCore()
  {
    return $this->transform(
      dirname( __FILE__ ) . "/marcxml_to_dublincore.xsl",
      $this->getXml()
    );
  }

  public function toMods()
  {
    return $this->transform(
      dirname( __FILE__ ) . "/marcxml_to_mods.xsl",
      $this->getXml()
    );
  }
}


/**
 * Oai marc record
 */
class YAZ_OaiMarcXmlResult extends YAZ_MarcXmlResult
{
  protected $rootStartTag = '<collection xmlns="http://www.openarchives.org/OIA/oai_marc">';

  public function getXml()
  {
    return $this->transform(
      dirname( __FILE__ ) . "/oaimarc_to_marcxml.xsl",
      parent::getXml()
    );
  }
}

