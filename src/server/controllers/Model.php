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
     * Chritian Boulanger (cboulanger)

************************************************************************ */




/**
 * Provides services based on a generic model API, using datasource
 * and modelType information
 */
class bibliograph_service_Model
  extends qcl_data_controller_TableController
{

  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $modelAcl = array(

    /*
     * the datasource model itself. anonymous can only
     * read the namedId property.
     */
    array(
      'datasource'  => "",
      'modelType'   => "",
      'roles'       => "*",
      'rules'         => array(
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" => array( NAMED_ID, "title", "description" ) )
        ),
        array(
          'roles'       => array(
            BIBLIOGRAPH_ROLE_ADMIN,
            BIBLIOGRAPH_ROLE_MANAGER
          ),
          'access'      => "*",
          'properties'  => array( "allow" => "*" )
        )
      )
    ),
    /*
     * the other models - give read access
     */
    array(
      'datasource'  => "*",
      'modelType'   => "reference",
      'roles'       => "*",
      'rules'         => array(
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" => "*" )
        ),
        array(
          'roles'       => array(
            BIBLIOGRAPH_ROLE_ADMIN
          ),
          'access'      => "*",
          'properties'  => array( "allow" => "*" )
        )
      )
    ),
    /*
     * access to user data
     */
    array(
      'datasource'  => "access",
      'modelType'   => "user",
      'rules'         => array(
        array(
          'roles'       => array( QCL_ROLE_USER ),
          'access'      => array( QCL_ACCESS_READ, QCL_ACCESS_WRITE ),
          'properties'  => array( "allow" => "*" )
        )
      )
    )
  );

  /**
   * Record access control list. Determines access to individual
   * result rows based on specific rules
   *
   * @var array
   */
  private $recordAcl = array(
    array(
      'datasource'  => "",
      'modelType'   => "",
      'rules'         => array(
        array(
          'callback'   => "checkDatasourceAccess"
        )
      )
    )
  );

  /*
  ---------------------------------------------------------------------------
     CLASS PROPERTIES
  ---------------------------------------------------------------------------
  */

  /**
   * Whether datasource access should be restricted according
   * to the current user. The implementation of this behavior is
   * done by the getAccessibleDatasources() and checkDatasourceAccess()
   * methods.
   *
   * @var bool
   */
  protected $controlDatasourceAccess = true;

  /*
  ---------------------------------------------------------------------------
     INTIALIZATION
  ---------------------------------------------------------------------------
  */

  /**
   * Constructor, adds model acl
   */
  function __construct()
  {
    $this->addModelAcl( $this->modelAcl );
    $this->addRecordAcl( $this->recordAcl );
  }


  /*
  ---------------------------------------------------------------------------
     INTERNAL METHODS
  ---------------------------------------------------------------------------
  */

  /*
  ---------------------------------------------------------------------------
     API
  ---------------------------------------------------------------------------
  */

  /**
   * Returns a list of datasources that is accessible to the current user
   *
   * @return unknown_type
   */
  public function method_getDatasourceListData()
  {
    $listData     = array();
    $dsModel      = $this->getDatasourceModel();

    /*
     * find all datasources this groups have access to
     */
    $datasources =  $this->getAccessibleDatasources();
    $where = "`namedId` IN('" . implode("','", $datasources ) . "')"; // @todo rewrite when qcl contains an adequate query parser
    $query = new qcl_data_db_Query( array(
      'where'   => $where,
      'orderBy' => "title"
    ) );
    $dsModel->find( $query );
    while( $dsModel->loadNext($query) )
    {
      /*
       * skip inactive Datasources
       */
      if ( ! $dsModel->isActive() or $dsModel->isHidden() )
      {
        continue;
      }

      /*
       * build list items
       */
      $listData[] = array(
        'value' => $dsModel->namedId(),
        'label' => either( $dsModel->getTitle(), $dsModel->namedId() )
      );
    }

    /*
     * send list data to client
     */
    return $listData;
  }

  /**
   * Returns a client copy of the datasource model
   * @todo generalize this
   * @param $datasource
   * @return array
   */
  public function method_getDatasourceModelData( $datasource )
  {
    $this->checkDatasourceAccess( $datasource );
    $datasourceModel = $this->getDatasourceModel( $datasource );
    return $datasourceModel->clientData();
  }

  /**
   * Returns information on  services that provide list view data.
   *
   * @param $datasource
   * @return array of maps containing information on type of data,
   * and the service name providing the data
   * @todo use response class instead of array
   * @todo deprecated
   */
  function method_getListViewServices( $datasource )
  {
    $this->checkDatasourceAccess( $datasource );

    return array(
      array(
       'type'    => "reference",
       'service' => "bibliograph.reference",
       'label'   => "References ($datasource)"
      )
    );
  }

  /**
   * Returns the type of the model that contains tabular data in the
   * datasource.
   *
   * @param $datasource
   * @return string
   * @todo this is stupid. what if several models provide tabular data?
   */
  public function method_getTableModelType( $datasource )
  {
    $datasourceModel = $this->getDatasourceModel( $datasource );#
    return $datasourceModel->getTableModelType();
  }

}
