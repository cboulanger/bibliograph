<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2010 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_core_Object" );
qcl_import( "qcl_core_PersistenceBehavior" );
qcl_import( "qcl_core_IPersistable" );

/**
 * Class that will be persisted between requests. The particular implementation
 * of the persistence is done by the persistence behavior object. Avoid instantiating
 * more than one instance of this object during run-time, since this will create
 * problems at destruct time - it is hard to foresee which object saves its data
 * last. Use a singleton or global variable, or store a reference to the object
 * in a static member of the class you need it.
 */
class qcl_core_PersistentObject
  extends    qcl_core_Object
  implements qcl_core_IPersistable
{
  /**
   * A referece to the last instance instantiated, to avoid that
   * many instances linger in memory to be saved at destruct time.
   * @var array
   */
  private static $instance = array();

  /**
   * Whether this is a newly instantiated object. Will be turned to false
   * when retrieved from cache.
   * @var bool
   */
  private $isNew = false;

  /**
   * Whether the data is disposed, i.e. not persisted any longer
   * @var bool
   */
  private $isDisposed = false;

  /**
   * Constructor
   */
  function __construct ()
  {
    /**
     * Initializing qcl_core_Object
     */
    parent::__construct();

    /*
     * deserialize properties from the cache. If the restore() method of
     * the property behavior returns true, object data has been restored,
     * which means the object is no longer "new".
     */
    $this->isNew =
      ! $this
        ->getPersistenceBehavior()
        ->restore( $this, $this->getPersistenceId() );

    /*
     * save a reference to the last created instance of this class,
     * only this instance will be saved in the end.
     */
    //echo "/* instantiating " . $this->objectId() . "*/";
    self::$instance[get_class($this)] = $this;
  }

  /**
   * Whether the object has been newly created (true) or has been restored
   * from a cache
   * @return bool
   */
  public function isNew()
  {
    return $this->isNew;
  }

  /**
   * Whether the data is disposed, i.e. not persisted any longer
   * @return bool
   */
  public function isDisposed()
  {
    return $this->isDisposed;
  }

  /**
   * Getter for persistence behavior.
   * @return qcl_core_PersistenceBehavior
   */
  function getPersistenceBehavior()
  {
    return qcl_core_PersistenceBehavior::getInstance();
  }

  /**
   * Returns the id that is used to persist the object between
   * requests. Defaults to the class name, so that each new
   * object gets the content of the last existing object of the
   * same class. Override for different behavior.
   * @return string
   */
  function getPersistenceId()
  {
    return $this->className();
  }

  /**
   * Persist the properties of the object so that they will be
   * restored upon next instantiation of the object.
   * @return void
   */
  public function savePersistenceData()
  {
    $this->getPersistenceBehavior()->persist( $this, $this->getPersistenceId() );
    $this->isDisposed = false;
  }

  /**
   * Clears all data stored for the object
   * @return void
   */
  public function disposePersistenceData()
  {
    $this->getPersistenceBehavior()->dispose( $this, $this->getPersistenceId() );
    $this->isDisposed = true;
  }

  /**
   * Destructor. Calls savePersistenceData() if the data hasn't been
   * disposed.
   */
  function __destruct()
  {
    try
    {
      //echo "\n\n/* destroying " .  $this . ", last instance " . self::$instance[get_class($this)] . " */";
      if ( ! $this->isDisposed() and self::$instance[get_class($this)] === $this )
      {
        //echo "\n/* saving " .  $this  . " */";
        //echo "\n/* saving " .  print_r( $this->data(), true)  . " */";
        $this->savePersistenceData();
      }
    }
    catch( Exception $e )
    {
      echo "/* Exception caught in destructor: $e */";
    }
  }
}
