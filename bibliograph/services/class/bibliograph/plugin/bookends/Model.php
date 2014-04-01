<?php
/*
 * dependencies
 */


/**
 * Class representing a bookends record. Data manipulation
 * is limited to uploading new records and downloading formatted
 * record data through http.
 */
class bibliograph_plugins_bookends_Model extends bibliograph_model_record_default
{
  /**
   * Path to schema xml
   * @var string
   */
  var $schemaXmlPath ="bibliograph/plugins/bookends/Model.xml";

  /**
   * Request object
   * @var qcl_http_request
   */
  var $request;

  /**
   * The url of the bookends server
   */
  var $url = null;

  /**
   * Database to use
   */
  var $database;

  /**
   * Wheter a successful connection has been established
   * @var bool
   */
  var $isConnected = false;

  /**
   * Timeout for connection request. Defaults to 3 seconds
   */
  var $timeout = 3;

  /**
   * How many seconds to wait before resending a request
   * if the last was unsuccessful
   */
  var $retryAfter = 3;

  /**
   * How many attempts to send a request before raising
   * an exception
   */
  var $maxAttempts = 7;

  /**
   * Flag that last query found no matching records
   * @var bool
   */
  var $_foundNoMatches;

  /**
   * Setter for url
   */
  function setUrl($url)
  {
    $this->url = $url;
  }

  /**
   * Getter for database
   */
  function getUrl()
  {
    if ( ! $this->url )
    {
      $this->error = "No url set for Bookends server connection";
    }
    return $this->url;
  }

  /**
   * Setter for database
   */
  function setDatabase($database)
  {
    $this->database = $database;
  }

  /**
   * Getter for database
   */
  function getDatabase()
  {
    if ( ! $this->database )
    {
      $this->error = "No database set for Bookends server connection";
    }
    return $this->database;
  }


  /**
   * Connects to the Bookend server. Setup the needed objects for the http connection
   * and request decoding.
   * @param string|null[optional,default null] $url
   * @param string|null[optional,default null] $database
   * @return bool
   * @override
   */
  function connect($url=null,$database=null)
  {
    $controller =& $this->getController();

    if ( $url and $database)
    {
      $this->setUrl($url);
      $this->setDatabase($database);
    }
    else
    {
      $dsModel =& $this->getDatasourceModel();

      if ( is_object($dsModel) )
      {
        $this->setUrl( $dsModel->getUrl() );
        $this->setDatabase( $dsModel->getDatabase() );
      }
      else
      {
        // do nothing
        $this->warn("No datasource model. Cannot connect.");
        return false;
      }
    }

    /*
     * request object
     */
    $this->request =& new qcl_http_request($controller);
    $this->request->setUrl( $this->getUrl() . "/BEPost");
    $this->request->setTimeout( $this->timeout );

    /*
     * check connection by launching a request
     * that should return no records.
     */
    /*if ( $this->is )
    //{
      $this->findByAuthor('XYZ');

      $error = new String($this->getError());
      if ( $error->contains("can't be accessed") )
      {
        $this->isConnected = false;
        return false;
      }
    //}*/

    /*
     * Clear the error and return successfully
     */
    $this->setError(null);
    $this->isConnected = true;
    return true;
  }


  /**
   * Bookends record model cannot and does not
   * need to setup a schema.
   */
  function setupSchema() {}

  /**
   * Create a new record
   * @param string $reftype Reference type. Must be set.
   * @param null $citekey
   * @param array $record
   * @internal param $string [optional] $citekey Unique citation identifier,
   * doesn't need to be the final value, but must be unique among
   * the existing records. If not provided, a random hash key will be used
   * @internal param array $data Initial data of the record
   * @override
   * @return int Unique numeric id in bookends database
   */
  function create( $reftype=null, $citekey=null, $record=array() )
  {
    /*
     * check parameters
     */
    if ( is_null($reftype) )
    {
      $this->setError("You need to specify the reference type.");
      return false;
    }

    if ( is_null($citekey) )
    {
      $citekey = "temp_" . md5(microtime());
    }

    /*
     * set record
     */
    $record['id']      = null;
    $record['citekey'] = $citekey;
    $record['reftype'] = $reftype;

    $this->setRecord( $record );

    /*
     * bookends database
     */
    $database = $this->getDatabase();
    $this->log("Creating new record in '$database', type '$reftype', citekey '$citekey' ...", "sync");

    /*
     * Assemble request data
     */
    $data = array();
    $data['DB'] =  $database;
    $data['Filter'] = "Bibliograph";
    $data['textToImport'] = $this->toExchangeFormat( array($this->getRecord()) );
    $this->request->setData($data);

    $attempt = 0;
    while ( $attempt++ < $this->maxAttempts )
    {
      /*
       * send request
       */
      $this->request->send();

      /*
       * check response text for errors
       */
      $response = new String(trim(strip_tags($this->request->getResponseContent())));

      $this->log("Bookend Server at {$this->url} says: " . $response->toString(), "sync");

      /*
       * success
       */
      if ( $response->contains("1 reference imported") )
      {
        /*
         * Search for citekey to get unique id
         */
        $this->findByCitekey( $citekey );
        //$this->info($this->getRecord());

        if ( $this->foundNothing() or ! $this->getId() )
        {
          $this->setError("Could not get unique id for citekey '$citekey'");
          return false;
        }

        $id = $this->getId();

        /*
         * return id
         */
        $this->log("Created new record #$id","sync");
        return $id;
      }

      /*
       * error
       */
      if ( ! $response->isEmpty()  )
      {
        $this->setError("Failed to create reference in Bookends server: " . $response->toString() );
        return false;
      }

      /*
       * no response, try again
       */
      $this->info("Attempt #$attempt to create bookends record failed.");
      sleep($this->retryAfter);
    }

    /*
     * abort
     */
    $this->setError("Aborting after {$this->maxAttempts} unsucessful attempts to create a bookends record.");
    return false;

  }

  /**
   * Updates the current record by submitting it to the
   * Bookends server
   * @param array|null $data If provided, use this data instead of active record
   * @param int|null $id If provided, use this id instead of id of active record
   * @return bool
   * @throws InvalidArgumentException
   * @return bool
   */
  function update( $data=null, $id=null )
  {

    /*
     * data
     */
    $data = either( $data, $this->getRecord() );
    if ( ! is_array($data) )
    {
      throw new InvalidArgumentException("Cannot update: No data.");
    }

    /*
     * get id of record from either the given parameter,
     * the id in the given data, or from active record
     */
    if ( ! ( $id = either( $id, $data['id'] ) ) )
    {
      $this->getProperty('id');
    }
    if ( ! $id )
    {
      throw new InvalidArgumentException("Cannot update: no id provided.");
    }

    /*
     * copy properties
     * @todo Why not use unschematize here?
     */
    $record = $this->convertToBookends( $data );

    /*
     * unique id and database
     */
    $data = array();
    $data['updateUniqueId'] = $id;
    $data['DB'] = $this->getDatabase();
    foreach ( $record as $property => $value )
    {

      /*
       * skip reference type and id
       */
      if ( $property == "reftype" or $property == "id" ) continue;

      /*
       * set property value in request data
       */
      $column = $this->getColumnName($property);
      $data[$column] = $value;
    }

    //$this->debug($data);

    $attempt = 0;

    while ( $attempt++ < $this->maxAttempts )
    {
      /*
       * send request
       */
      $this->_foundNoMatches = false;
      $this->request->setData($data);
      $this->request->send();

      /*
       * log response text
       */
      $response = new String(trim(strip_tags($this->request->getResponseContent())));
      //$this->debug("Bookend Server at {$this->url} says: " . $response->toString());

      /*
       * check for errors
       */
      if ( $response->contains("successfully updated") )
      {
        /*
         * request was sucessful
         */
        return true;
      }
      elseif ( $response->contains("No matching reference"))
      {
        /*
         * the reference that was to be updated doesn't exist (anymore)
         */
        $this->setError( "Database '" . $this->getDatabase() . "' does not contain a reference with id #$id." );
        $this->_foundNoMatches = true;
        return false;
      }
      elseif ( ! $response->isEmpty() )
      {
        $this->setError( $response->toString() );
        return false;
      }

      /*
       * try again
       */
      $this->info("Update attempt #$attempt failed.");
      sleep($this->retryAfter);
    }

    /*
     * abort
     */
    $this->setError("Aborting after {$this->maxAttempts} unsucessful update attempts.");
    return false;
  }

  /**
   * Retrieves a single bookends record
   * @param array|string $where Whre condition to match
   * @param string $orderBy
   * @param string $limit
   * @return array| Array if result was successful, False if an error
   * occurred, empty array if no records where found.
   */
  function findWhere( $where, $orderBy="", $limit="" )
  {
    $this->_foundNoMatches = false;

    /*
     * prepare request
     */
    $this->request->setData(array(
      'DB'          => $this->getDatabase(),
      'SQLQuery'    => $this->toSql($where),
      'Format'      => "Bibliograph",
      'SendToFile'  => "Text File",
      'SortBy'      => $orderBy,
      'HitLimit'    => $limit
    ));

//$this->debug($this->request->getData());

    $attempt     = 0;

    while ( $attempt++ < $this->maxAttempts )
    {

      /*
       * send request and get data
       */
      $this->request->send();
      $response = $this->request->getResponseContent();
//$this->debug("Server says: '$response'");

      if ( trim($response) )
      {
        break;
      }

      /*
       * timeout or other error, try again
       */
      $error = $this->request->getError();
      if ( ! $error )
      {
        $error = "No Response";
      }
      $this->info("Query attempt #$attempt failed: $error");
      sleep($this->retryAfter);

    }


    /*
     * abort after three unsucessful connection attempts
     */
    if ( $attempt == $this->maxAttempts )
    {
      $response = "Aborting after {$this->maxAttempts} unsucessful query attempts.";
      $result   = false;
    }
    else
    {
      $result = $this->fromExchangeFormat($response);
    }

    /*
     * check and return result
     */
    if ( count($result) )
    {
      $this->setRecord( $result[0] );
      $this->setResult( $result );
      return $result;
    }
    else
    {
      $this->setRecord( null );
      $this->setResult( null );
      $error = new String( trim( strip_tags($response) ) );
      if( $error->contains("No matches") )
      {
        $this->setError(null);
        $this->_foundNoMatches = true;
        return null;
      }
      else
      {
        $this->setError($error->toString());
        return false;
      }
    }
  }

  /**
   * Returns true if the last query did not produce any mathing
   * records.
   * @return bool
   */
  function foundNoMatches()
  {
    return $this->_foundNoMatches;
  }

  /**
   * Finds a record by unique id
   * @param int $id
   * @return array
   * @override
   */
  function findById($id)
  {
    return $this->findBy("id",$id);
  }

  /**
   * Finds a records by property value
   * @param string $propName Name of property
   * @param string $value Value to find
   * @return array recordset
   */
  function findBy( $propName, $value )
  {
    $propName = trim( $propName );
    $propType = $this->getPropertyType($propName);

    if ( $propType=="int" )
    {
      return $this->findWhere( array( $propName => "=" . $value) );
    }
    else
    {
      return $this->findWhere( array( $propName => "='" . addslashes($value) ."'" ) );
    }

  }

  /**
   * Get error message
   * @override
   */
  function getError()
  {
    return "Bookends connection error: " . parent::getError();
  }

  /**
   * Converts array data to a 'where' compliant sql string
   * @param string|array $where
   * @return array|string
   * @override
   */
  function toSql( $where )
  {
    if ( is_array($where) )
    {
      $sql = "";
      foreach ( $where as $property => $expr )
      {
        $sql .= $this->getColumnName($property) . " " . $expr . " ";
      }
      return $sql;
    }
    else
    {
      return $where;
    }
  }

  //// Implementing MRemoteTableDataModel

  /**
   * @todo this is wasteful
   */
  function getRowCount( $queryData )
  {
    $where = $queryData->query;
    //$requestId = $queryData->requestId;
    $this->findWhere( $where );
    return $this->countResult();
  }

  function getRowData ( $queryData, $first, $last )
  {
    $where     = $queryData->query;
    $columns   = explode(",",$queryData->columns);
    //$requestId = $queryData->requestId;
    $this->findWhere( $where );
    $this->debug($this->getResult());
    $records = array(); $i=0;
    if ( ! $this->foundNoMatches() ) do
    {
      /*
       * we have no other way to enforce the limits
       * than to skip the rest
       */
      if ( $i < $first or $i > $last ) continue;

      /*
       * now get requested records and properties
       */
      $record = $this->getRecord();
      foreach( $record as $key => $value )
      {
        if ( $key=="date" ) $key="year";
        if ( in_array($key, $columns) )
        {
          $records[$i][$key] = $value;
        }
      }

      $records[$i]['icon'] = "";
      $i++;
    }
    while( $this->nextRecord() );
    return $records;
  }

  //// Exchange format

  /**
   * Converts to the Bibliograph-Bookends exchange format
   * @param array $records Bibliograph-style records
   * @throws LogicException
   * @throws InvalidArgumentException
   * @return string interchange format
   */
  function toExchangeFormat( $records )
  {

    /*
     * check parameters
     */
    if ( ! count( $records ) )
    {
      throw new InvalidArgumentException("No valid records!");
    }

    $string = "";

    /*
     * loop through all records
     */
    foreach($records as $record)
    {

      /*
       * convert values to bookends conventions
       */
      $record = $this->convertToBookends($record);

      /*
       * reference type must be first entry
       */
      $string .= "RT " . $record['reftype'] ."\n";
      unset($record['reftype']);

      /*
       * loop through each record
       */
      foreach( $record as $key => $value)
      {
        /*
         * get property node in schema xml containing
         * information on the property
         */
        if ( ! $this->hasProperty($key) ) continue;

        $propNode =& $this->getPropertyNode($key);

        if ( $propNode )
        {
          /*
           * get the tag name for the property
           */
          $attrs = $propNode->attributes();
          $tag   = (string) $attrs['tag'];

          if ( ! $tag )
          {
            throw new LogicException("Property $key has no 'tag' attribute!");
          }

          /*
           * add line
           */
          if ( trim($value) )
          {
            $string .= "$tag $value\n";
          }
        }
      }
      $string .= "\n";

    }
    return $string;
  }

  /**
   * Converts record values from Bibliograph to Bookends conventions
   */
  function convertToBookends( $record )
  {
    /*
     * check parameter
     */
    if ( ! is_array($record) )
    {
      throw new InvalidArgumentException("Invalid parameter");
    }

    $convRec = array();
    foreach ( $record as $key => $value )
    {
      /*
       * volume/issue
       */
      if ( $key == "volume" )
      {
        if ( $value and $record['number'] )
        {
          $value = "$value ({$record['number']})";
        }
      }
      elseif ($key == "number" )
      {
        if ( $record['volume'] )
        {
          continue;
        }
      }

      /*
       * collection: copy values from "author" field
       */
      if ( $key == "editor" and empty($value) and $record['reftype'] == "collection")
      {
        $value = $record['author'];
        unset( $record['author'] );
        unset( $convRec['author'] );
      }

      /*
       * save value in array
       */
      $convRec[$key] = $value;
    }
    return $convRec;
  }

  /**
   * Converts from Bibliograph-Bookends exchange schema to
   * Bibliograph record.
   * @param string $string
   * @return array
   */
  function fromExchangeFormat($string)
  {
    /*
     * generate tag list
     * @todo cache result
     */
    $tagList = array();
    foreach ( $this->getProperties() as $name )
    {
      $propNode =& $this->getPropertyNode($name);
      $attrs = $propNode->attributes();
      $tag   = (string) $attrs['tag'];
      $tagList[$tag] = $name;
    }

    //$this->Info($tagList);

    /*
     * parse document
     */
    $string = new String($string);
    $lines  = $string->split("/\r\n|\r|\n/");
    $result = array();
    $index  = -1;

    foreach( $lines as $line )
    {

      if ( trim($line) and $line{2} == " " )
      {
        $l     = new String($line);
        $tag   = $l->substr(0,2);
        $value = $l->substr(3);
        $key   = $tagList[$tag];
        if ( trim( $tag ) and trim( $value ) )
        {
          /*
           * each record starts with the reference type
           */
          if ( $key == "reftype" )
          {
            $index++;
          }

          $result[$index][$key] = $value;
        }
        //$this->info("$tag => $key => $value");
      }

      if ( is_array( $result[$index] ) )
      {
        $result[$index] = $this->convertToBibliograph($result[$index]);
      }
    }

    //$this->info($result);
    return $result;
  }


  /**
   * Converts record values from Bookends to Bibliograp conventions
   * @param array $record
   * @throws InvalidArgumentException
   * @return array
   */
  function convertToBibliograph ( $record )
  {
    /*
     * check parameter
     */
    if ( ! is_array($record) )
    {
      throw new InvalidArgumentException("Invalid parameter");
    }

    $convRec = array();

    foreach ( $record as $key => $value )
    {
      /*
       * output format bug for person fields
       */
      if ( $key == "author" or $key=="editor" )
      {
        $comma = false;
        for($i=0;$i<strlen($value);$i++)
        {
          if ( $value[$i] == "," )
          {
            if ( $comma )
            {
              $value[$i] = ";";
              $comma = false;
            }
            else
            {
              $comma = true;
            }
          }
          elseif ( $value[$i] == ";" )
          {
            $comma = false;
          }
        }
      }

      /*
       * save value in array
       */
      $convRec[$key] = $value;
    }
    return $convRec;
  }

  /**
   * A bookends database has no exposed indexes, but uses
   * "allFields" as an indexed search field
   * @return array
   */
  function indexes()
  {
    return array();
  }
}