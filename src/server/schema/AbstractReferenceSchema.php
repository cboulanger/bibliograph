<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

namespace app\schema;

use InvalidArgumentException;
use lib\schema\ISchema;
use Yii;


/**
 * Base class for schemas
 */
abstract class AbstractReferenceSchema extends yii\base\BaseObject implements ISchema
{

  /**
   * The default record type
   * @var string
   */
  protected $defaultType = "";

  /**
   * An array of fields that are part of the data
   * regardless of the record type and are prepended
   * to the record-specific fields
   * @var array
   */
  protected $defaultFieldsBefore = array();

  /**
   * An array of fields that are part of the data
   * regardless of the record type and are appended
   * to the record-specific fields
   * @var array
   */
  protected $defaultFieldsAfter = array();

  /**
   * The fields that are part of the form by default,
   * regardless of record type
   * @var array
   */
  protected $defaultFormFields = array();

  /**
   * The reference types with their fields
   * @var array
   */
  protected $type_fields;

  /**
   * The reference type fields
   * @var array
   */
  protected $field_data;

  /**
   * The metadata of the types
   * @var array
   */
  protected $type_data;

  /**
   * The default reference type
   * @return string
   */
  public function defaultType()
  {
    return $this->defaultType;
  }

  /**
   * Returns an array of reference types supported by this schema
   * @return array
   */
  public function types()
  {
    return array_keys($this->type_data);
  }

  /**
   * An array of fields supported by the schema
   * @return array
   */
  public function fields()
  {
    return array_keys($this->field_data);
  }

  /**
   * Adds or replaces reference type data
   * @param array $types
   * @return void
   */
  public function addTypes($types)
  {
    foreach ($types as $name => $data) {
      $this->type_data[$name] = $data;
    }
  }

  /**
   * Returns an array with the field names that should
   * be in the schema regardless of record type,
   * prepended to the record type fields.
   * @return array
   *    The array passed as a reference, so you can manipulate
   *    it.
   */
  public function &getDefaultFieldsBefore()
  {
    return $this->defaultFieldsBefore;
  }


  /**
   * Returns an array with the field names that should
   * be in the schema regardless of record type,
   * appended to the record type fields.
   * @return array
   *    The array passed as a reference, so you can manipulate
   *    it.
   */
  public function &getDefaultFieldsAfter()
  {
    return $this->defaultFieldsAfter;
  }


  /**
   * Returns an array with the names of field that should
   * be displayed in the form for editing the record,
   * regardless of record type.
   * @return array
   *    The array passed as a reference, so you can manipulate
   *    it.
   */
  public function &getDefaultFormFields()
  {
    return $this->defaultFormFields;
  }

  /**
   * Adds or replaces reference field data
   * @param $fields
   * @internal param array $types
   * @return void
   */
  public function addFields(array $fields)
  {
    foreach ($fields as $name => $data) {
      $this->field_data[$name] = $data;
    }
  }

  /**
   * Returns an array of fields that belong to a type
   * @param string $type
   * @throws InvalidArgumentException
   * @return array
   */
  public function getTypeFields($type)
  {
    if (!isset($this->type_fields[$type])) {
      throw new InvalidArgumentException("Type '$type' does not exist.");
    }
    return $this->type_fields[$type];
  }

  /**
   * Adds fields to the end of the fields of each reference type
   * @param $fields
   * @return void
   */
  public function addToTypeFields(array $fields)
  {
    foreach ($this->types() as $type) {
      $this->type_fields[$type] = array_unique(array_merge(
        $this->type_fields[$type], $fields
      ));
    }
  }


  /**
   * Returns the definition of a field
   * @param string $field
   * @throws InvalidArgumentException
   * @return array
   */
  public function getFieldData($field)
  {
    if (!isset($this->field_data[$field])) {
      throw new InvalidArgumentException("Field '$field' does not exist.");
    }
    return $this->field_data[$field];
  }

  /**
   * Returns the definition of a field
   * @param $type
   * @throws InvalidArgumentException
   * @internal param string $field
   * @return array
   */
  public function getTypeData($type)
  {
    if (!isset($this->type_data[$type])) {
      throw new InvalidArgumentException("Type '$type' does not exist.");
    }
    return $this->type_data[$type];
  }

  /**
   * Returns the label for a field
   * @param string $field
   * @param string|null $reftype Optional reference type if there are different
   * labels for different reference types
   * @throws \RuntimeException
   * @return string
   */
  public function getFieldLabel($field, $reftype = null)
  {
    $data = $this->getFieldData($field);

    /*
     * get the label from field or form data
     */
    if (isset($data['label'])) {
      $label = $data['label'];
    } else {
      $formData = $this->getFormData($field);
      if (isset($formData['label'])) {
        $label = $formData['label'];
      } else {
        throw new \RuntimeException("Field '$field' has no label information!");
      }
    }

    /*
     * label is an array -> depends on the reference type
     */
    if (is_array($label)) {
      if ($reftype and isset($label[$reftype])) {
        return $label[$reftype];
      }
      $labels = array_values($label);
      return $labels[0];
    }
    return $data['label'];
  }

  /**
   * Returns the label for a reference type
   * @param string $reftype
   * @throws \Exception
   * @return string
   */
  public function getTypeLabel($reftype)
  {
    $data = $this->getTypeData($reftype);
    if (!isset($data['label'])) {
      throw new \Exception("Type '$reftype' has no label information!");
    }
    return $data['label'];
  }

  /**
   * Returns the form data of a field, if it is defined
   * @param $field
   * @return array
   */
  public function getFormData($field)
  {
    $data = $this->getFieldData($field);
    if (isset($data['formData'])) {
      return $data['formData'];
    }
    return null;
  }

  public function isPublicField($field)
  {
    $data = $this->getFieldData($field);
    return (
      !isset($data['public'])
      or $data['public'] !== false
    );
  }

  /**
   * Returns an associated array that maps the names of search index names
   * to an array of field names that are to be searched.
   * @return array
   */
  public function getIndexMap()
  {
    static $indexMap = null;
    if ($indexMap === null) {
      $indexMap=[];
      foreach ($this->field_data as $field => $data) {
        if (isset($data['index'])) {
          foreach ((array)$data['index'] as $index) {
            $indexMap[$index][] = $field;
          }
        }
      }
    }
    return $indexMap;
  }

  /**
   * Returns the translated index names for this schema
   * @return array Array of index names
   */
  public function getIndexNames()
  {
    return array_keys($this->getIndexMap());
  }

  /**
   * Returns true if index exists, false if not
   * @param string $index
   * @return bool
   */
  public function hasIndex($index)
  {
    $indexMap = $this->getIndexMap();
    return isset($indexMap[$index]);
  }

  /**
   * Returns the fields associated to a search index
   * @param string $index
   * @throws InvalidArgumentException
   * @return array
   */
  public function getIndexFields($index)
  {
    $indexMap = $this->getIndexMap();
    if (isset($indexMap[$index])) {
      return $indexMap[$index];
    } else {
      throw new InvalidArgumentException("'$index' is not a valid index");
    }
  }
}
