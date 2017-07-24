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
 *
 */
abstract class bibliograph_model_import_AbstractImporter
  extends qcl_core_Object
{
  /**
   * The id of the format
   * @var string
   */
  protected $id;

  /**
   * The name of the format
   * @var string
   */
  protected $name;

  /**
   * The type of the format
   * @var string
   */
  protected $type;

  /**
   * The file extension of the format
   * @var string
   */
  protected $extension;
  
  /**
   * The description of the format
   * @var string
   */
  protected $description;  

  /**
   * Getter for id
   * @return string
   */
  public function getId()
  {
    qcl_assert_valid_string($this->id, $this->className() . " has no id.");
    return $this->id;
  }

  /**
   * Getter for name
   * @return string
   */
  public function getName()
  {
    qcl_assert_valid_string($this->id, $this->className() . " has no name.");
    return $this->name;
  }

  /**
   * Getter for description
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Getter for type
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Getter for extension
   * @return string
   */
  public function getExtension()
  {
    return $this->extension;
  }

  /**
   * Parses string data into a bibliograph-compatible array.
   *
   * @param string $data
   *    A string containing import data
   * @param bibliograph_model_ReferenceModel $targetModel
   *    The model in which to import the data (for information about the model)
   * @return array
   *    The result array which can be imported into bibliograph
   *
   */
  abstract function import( $data, bibliograph_model_ReferenceModel $targetModel );

}
