<?php

namespace lib\models;

use lib\schema\ISchema;

/**
 * Interface to mark that a model has a Schema class that describes
 * its data model with meta data. The structure of this metadata depends
 * on the implementation.
 * @package lib\models
 */
interface IHasSchema {

  /**
   * Returns the schema object used by this model
   * @return ISchema
   */
  public static function getSchema(): ISchema;
}
