<?php

namespace app\controllers\traits;

/**
 * Trait for creating and evaluating form data
 */
trait FormTrait
{
  /**
   * Returns data for a dialog.Form widget based on a model
   * @param qcl_data_model_AbstractActiveRecord $model
   * @param int $width The default width of the form in pixel (defaults to 300)
   * @throws JsonRpcException
   * @throws InvalidArgumentException
   * @return array
   */
  protected function createFormData(qcl_data_model_AbstractActiveRecord $model, $width = 300)
  {
    $modelFormData = $model->formData();

    if (! is_array( $modelFormData) or ! count( $modelFormData )) {
      throw new JsonRpcException( "No form data exists.");
    }

    $formData = array();

    foreach ($modelFormData as $name => $elementData) {
      /*
       * dynamically get element data from the object
       */
      if (isset( $elementData['delegate'] )) {
        qcl_assert_array( $elementData['delegate'] );
        foreach ($elementData['delegate'] as $key => $delegateMethod) {
          qcl_assert_method_exists( $model, $delegateMethod );
          $elementData[$key] = $model->$delegateMethod( $name, $key, $elementData );
        }
        unset( $elementData['delegate'] );
      }

      /*
       * check property data
       */
      qcl_assert_valid_string( $elementData['label'] );

      /*
       * type
       */
      if (! isset( $elementData['type'] )) {
        $elementData['type']  = "TextField";
      }

      /*
       * width
       */
      if (! isset( $elementData['width'] )) {
        $elementData['width'] = $width;
      }

      /*
       * get value from model or default value
       */
      if (! isset( $elementData['value'] )) {
        $elementData['value'] = $model->get( $name );
      }
      if (isset( $elementData['default'] )) {
        if (! $elementData['value']) {
          $elementData['value'] = $elementData['default'];
        }
        unset( $elementData['default'] );
      }

      /*
       * marshal value
       */
      if (isset( $elementData['marshaler'] )) {
        if (isset( $elementData['marshaler']['marshal'] )) {
          $marshaler = $elementData['marshaler']['marshal'];
          if (isset( $marshaler['function'] )) {
            $elementData['value'] = $marshaler['function']( $elementData['value'] );
          } elseif (isset( $marshaler['callback'] )) {
            $callback = $marshaler['callback'];
            qcl_assert_array( $callback );
            if ($callback[0] == "this") {
              $callback[0] = $model;
            }
            qcl_assert_method_exists( $callback[0], $callback[1] );
            $elementData['value'] = $callback[0]->$callback[1]( $elementData['value'] );
          } else {
            throw new InvalidArgumentException("Invalid marshalling data");
          }
        }
        unset( $elementData['marshaler'] );
      }
      $formData[ $name ] = $elementData;
    }
    return $formData;
  }

  /**
   * Parses data returned by  dialog.Form widget based on a model
   * @param qcl_data_model_AbstractActiveRecord $model
   * @param object $data ;
   * @throws JsonRpcException
   * @throws InvalidArgumentException
   * @return array
   */
  protected function parseFormData(qcl_data_model_AbstractActiveRecord $model, $data)
  {
    $data = object2array( $data ) ;
    $modelFormData = $model->formData();

    if (! is_array( $modelFormData) or ! count( $modelFormData )) {
      throw new JsonRpcException( "No form data exists");
    }
    foreach ($data as $property => $value) {
      /*
       * is it an editable property?
       */
      if (! isset( $modelFormData[$property] )) {
        throw new JsonRpcException( "Invalid form data property '$property'");
      }

      /*
       * should I ignore it?
       */
      if (isset( $modelFormData[$property]['ignore'] ) and $modelFormData[$property]['ignore'] === true) {
        unset( $data[$property] );
        continue;
      }

      /*
       * marshaler
       */
      if (isset( $modelFormData[$property]['marshaler']['unmarshal'] )) {
        $marshaler = $modelFormData[$property]['marshaler']['unmarshal'];
        if (isset( $marshaler['function'] )) {
          $value = $marshaler['function']( $value );
        } elseif (isset( $marshaler['callback'] )) {
          $callback = $marshaler['callback'];
          qcl_assert_array( $callback );
          if ($callback[0] === "this") {
            $callback[0] = $model;
          }
          qcl_assert_method_exists( $callback[0], $callback[1] );
          $value = $callback[0]->$callback[1]( $value );
        } else {
          throw new InvalidArgumentException("Invalid marshaler data");
        }
        $data[$property] = $value;
      }

      /*
       * remove null values from data
       */
      if ($value === null) {
        unset( $data[$property] );
      }
    }
    return $data;
  }  

  /**
   * Function to check the match between the password and the repeated
   * password. Returns the hashed password.
   * @param $value
   * @throws JsonRpcException
   * @return string|null
   */
  public function checkFormPassword($value)
  {
    if (!isset($this->__password)) {
      $this->__password = $value;
    } elseif ($this->__password != $value) {
      throw new JsonRpcException($this->tr("Passwords do not match..."));
    }
    if ($value and strlen($value) < 8) {
      throw new JsonRpcException($this->tr("Password must be at least 8 characters long"));
    }
    return $value ? $this->getApplication()->getAccessController()->generateHash($value) : null;
  }
  
}