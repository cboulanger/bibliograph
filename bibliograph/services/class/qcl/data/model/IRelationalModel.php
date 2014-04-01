<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

/**
 * Interface for Active Record models that can be associated with
 * each other through foreign keys in the database
 */
interface qcl_data_model_IRelationalModel
{

  /**
   * Returns the relation behavior.
   * @return qcl_data_model_db_RelationBehavior
   */
  public function getRelationBehavior();

  /**
   * Add the definition of relations of this model for use in
   * queries.
   * @see qcl_data_model_IQueryBehavior::addRelations()
   * @param array $relations
   * @params string|null The name of the class that defines the relations
   * @return void
   */
  public function addRelations( $relations, $definingClass=null );

  /**
   * Returns true if the managed model has a relation with the given
   * model.
   *
   * @param qcl_data_model_db_ActiveRecord $model
   * @return bool
   */
  public function hasRelationWithModel( $model );

  /**
   * Creates a link between two associated models.
   * @param qcl_data_model_db_ActiveRecord $targetModel
   * @return bool True if new link was created, false if link
   *   already existed.
   */
  public function linkModel( $targetModel );

  /**
   * Checks if this model and the given target model are linked.
   *
   * @param qcl_data_model_db_ActiveRecord $targetModel Target model
   * @return bool
   */
  public function islinkedModel( $targetModel );

  /**
   * Unlinks the given target model from this model.
   *
   * @param qcl_data_model_db_ActiveRecord $targetModel Target model
   * @return bool
   */
  public function unlinkModel( $targetModel );


}
?>