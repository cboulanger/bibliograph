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

class qcl_access_LdapException extends JsonRpcException {}

/**
 * Class that maintains an LDAP connection. Only the methods necessary
 * to authenticate are implemented.
 *
 */
class qcl_access_LdapServer
  extends qcl_core_Object
{

  /**
   * Connection resource
   * @var resource
   */
  protected $ldap;

  /**
   * The name of the host or the url
   * @var string
   */
  protected $hostname;

  /**
   * The port used by the LDAP server
   * @var int
   */
  protected $host;

  /**
   * A search result
   * @var resource
   */
  protected $result;

  /**
   * Constructor. Takes the connection parameters as arguments.
   * @param string $hostname
   *    If you are using OpenLDAP 2.x.x you can specify a URL instead
   *    of the hostname. To use LDAP with SSL, compile OpenLDAP 2.x.x with
   *    SSL support, configure PHP with SSL, and set this parameter as
   *    ldaps://hostname/.
   * @param int $port
   *    The port to connect to. Not used when using URLs.
   * @param array $options
   * @return \qcl_access_LdapServer
   */
  public function __construct( $hostname, $port=389, $options=array() )
  {
    qcl_assert_valid_string( $hostname );
    qcl_assert_integer( $port );
    qcl_assert_array( $options );

    $this->hostname = $hostname;
    $this->port = $port;
    $this->options = $options;
  }

  /**
   * Establishes a connection to a LDAP server with the connection
   * parameters specified in the constructor
   * @throws qcl_access_LdapException
   * @return void
   */
  public function connect()
  {
    $ldap = @ldap_connect( $this->hostname, $this->port );
    if ( ! $ldap )
    {
      throw new qcl_access_LdapException("Could not connect to $this->hostname:$this->port.");
    }
    $this->ldap = $ldap;
  }

  /**
   * Binds to the LDAP directory with specified RDN and password.
   * @param string $rdn
   * @param string|null $password
   * @throws qcl_access_LdapException
   * @return void
   */
  public function bind( $rdn, $password=null )
  {
    qcl_assert_valid_string( $rdn );

    if ( ! @ldap_bind( $this->ldap, $rdn, $password ) )
    {
      throw new qcl_access_LdapException( ldap_error( $this->ldap), ldap_errno( $this->ldap ) );
    }
  }

  /**
   * Authenticates against this LDAP server. Connects and closes the connection
   * implicitly.
   * @param string $userdn
   * @param string $password
   * @throws qcl_access_AuthenticationException
   * @return void
   */
  public function authenticate( $userdn, $password )
  {
    try
    {
      $this->connect();
      $this->bind( $userdn, $password );
    }
    catch( qcl_access_LdapException $e )
    {
      $this->warn( $e->getMessage() );
      throw new qcl_access_AuthenticationException("LDAP Authentication failed.");
    }
  }

  /**
   * Searches the database
   * @param string $dn
   * @param string $filter
   * @param array $attrs
   * @throws qcl_access_LdapException
   * @return int Number of records found
   */
  public function search( $dn, $filter, $attrs=null )
  {
    $this->result = @ldap_search( $this->ldap, $dn, $filter, $attrs );
    if ( $this->result === false )
    {
      throw new qcl_access_LdapException( ldap_error( $this->ldap ), ldap_errno( $this->ldap ) );
    }
    return $this->countEntries();
  }

  /**
   * Returns the number of entries returned by the last search
   * @return int
   */
  public function countEntries()
  {
    return ldap_count_entries( $this->ldap, $this->result );
  }

  /**
   * Returns the complete search result as a multidimesional array
   * @return array
   */
  public function getEntries()
  {
    return ldap_get_entries( $this->ldap, $this->result );
  }

  /**
   * Closes the connection
   * @return void
   */
  public function close()
  {
    @ldap_close( $this->ldap );
  }

  /**
   * Destructor. Closes the connection.
   */
  public function __destruct()
  {
    $this->close();
  }
}
