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
qcl_import( "qcl_core_Object" );
qcl_import( "qcl_data_model_IModel");
qcl_import( "qcl_data_model_PropertyBehavior");

/**
 * Base class for all classes that implement a data model.
 * As opposed to qcl_core_Object, the properties of this object
 * are defined in a qooxdoo-style pattern.
 * @see qcl_data_model_PropertyBehavior
 */
class qcl_data_model_Model
  extends    qcl_core_Object
  implements qcl_data_model_IModel
{

  //-------------------------------------------------------------
  // Class properties
  //-------------------------------------------------------------

  /**
   * The property behavior object. Access with getPropertyBehavior()
   * @var qcl_core_IPropertyBehavior
   */
  private $propertyBehavior = null;


  //-------------------------------------------------------------
  // Property system
  //-------------------------------------------------------------

  /**
   * Returns the behavior object responsible for maintaining the object
   * properties and providing access to them. By default, use
   * qooxdoo-style property system.
   * @override
   * @return qcl_data_model_PropertyBehavior
   */
  public function getPropertyBehavior()
  {
    if ( $this->propertyBehavior === null )
    {
      $this->propertyBehavior = new qcl_data_model_PropertyBehavior( $this );
    }
    return $this->propertyBehavior;
  }

  /**
   * Add a property definition to the model and initialize the properties
   * @param array $properties
   * @return void
   */
  public function addProperties( $properties )
  {
    $this->getPropertyBehavior()->add( $properties );
  }
}
