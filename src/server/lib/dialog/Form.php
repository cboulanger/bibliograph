<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace lib\dialog;

use InvalidArgumentException;
use lib\models\BaseModel;
use yii\helpers\ArrayHelper;

class Form extends Dialog
{
  /**
   * Returns an event to the client which prompts the user with a form.
   *
   * @param string $message 
   *    The message text
   * @param array $formData 
   *    Arrray containing the form data. Example (using
   *    json instead of native php array):
   * <pre>
   * {
   *   'username' :
   *   {
   *     'type'  : "TextField",
   *     'label' : "User Name",
   *     'value' : ""
   *   },
   *   'address' :
   *   {
   *     'type'  : "TextArea",
   *     'label' : "Address",
   *     'lines' : 3
   *   },
   *   'domain'   :
   *   {
   *     'type'  : "SelectBox",
   *     'label' : "Domain",
   *     'value' : 1,
   *     'options' : [
   *       { 'label' : "Company", 'value' : 0 },
   *       { 'label' : "Home",    'value' : 1 }
   *     ]
   *   },
   *   'commands'   :
   *   {
   *    'type'  : "ComboBox",
   *     'label' : "Shell command to execute",
   *     'options' : [
   *       { 'label' : "ln -s *" },
   *       { 'label' : "rm -Rf /" }
   *     ]
   *   }
   * }
   * </pre>
   * @param bool $allowCancel
   *    Whether the form can be cancelled
   * @param string $callbackService 
   *    Service that will be called when the user clicks on the OK button
   * @param string $callbackMethod 
   *    Service method
   * @param array $callbackParams 
   *    Optional service params
   */
  public static function create(
    $message,
    $formData,
    $allowCancel=true,
    $callbackService,
    $callbackMethod,
    $callbackParams=null )
  {
    static::addToEventQueue( array(
       'type' => "form",
       'properties'  => array(
          'message'     => $message,
          'formData'    => $formData,
          'allowCancel' => $allowCancel,
          'maxWidth'    => 500 // FIXME Hardcoding this is BAD!
        ),
       'service' => $callbackService,
       'method'  => $callbackMethod,
       'params'  => $callbackParams
    ));
  }

  /**
   * Returns data for a dialog.Form widget based on a model
   * @param BaseModel  $model
   * @param int $width The default width of the form in pixel (defaults to 300)
   * @throws \Exception
   * @throws InvalidArgumentException
   * @return array
   */
  public static function getDataFromModel( BaseModel $model, $width = 300)
  {
    $modelFormData = $model->formData;
    if (! is_array( $modelFormData) or ! count( $modelFormData ) ) {
      throw new \Exception( "No form data exists.");
    }
    $widgetFormData = [];
    foreach ($modelFormData as $property => $field) {

      // add label
      if( ! isset($field['label'] ) ){
        $field['label'] = $model->getAttributeLabel($property);
      }

      // dynamically get element data from the object
      if ( isset( $field['delegate'] )) {
        foreach ($field['delegate'] as $key => $delegateMethod) {
          $field[$key] = $model->$delegateMethod( $property, $key, $field );
        }
        unset( $field['delegate'] );
      }

      // type
      if (! isset( $field['type'] )) {
        $field['type']  = "TextField";
      }

      // width
      if (! isset( $field['width'] )) {
        $field['width'] = $width;
      }

      // get value from model or default value
      if (! isset( $field['value'] )) {
        $field['value'] = $model->$property;
      }
      if (isset( $field['default'] )) {
        if (! $field['value']) {
          $field['value'] = $field['default'];
        }
        unset( $field['default'] );
      }

      // marshal a model property value for the form field's value
      $marshaler = ArrayHelper::getValue( $modelFormData, [$property,'marshal'] );
      if(is_callable($marshaler)) {
        $field['value'] = $marshaler($field['value']);
      } elseif( $marshaler) {
        throw new InvalidArgumentException("Invalid marshaller property for '$property': must be callable.");
      }

      $widgetFormData[ $property ] = $field;
    }
    return $widgetFormData;
  }

  /**
   * Parses data returned by  dialog.Form widget based on a model
   * @param BaseModel $model
   * @param object $data;
   * @throws \Exception
   * @throws InvalidArgumentException
   * @return array
   */
  public static function parseResultData(BaseModel $model, $data)
  {
    $data = json_decode(json_encode( $data ),true);
    $modelFormData = $model->formData;
    if (! is_array( $modelFormData) or ! count( $modelFormData ) ) {
      throw new InvalidArgumentException( 'Model has no valid form data.');
    }
    foreach ($data as $property => $value) {

      // is the property part of the form?
      if (! isset( $modelFormData[$property] )) {
        continue;
      }

      // should I ignore it?
      if ( ArrayHelper::getValue($modelFormData, [$property, 'ignore'],false) ) {
        unset( $data[$property] );
        continue;
      }

      // unmarshal form field values to be stored in a model property
      $unmarshaler = ArrayHelper::getValue( $modelFormData, [$property,'umarshal'] );
      if(is_callable($unmarshaler)) {
        $data[$property] = $unmarshaler($data[$property]);
      } elseif( $unmarshaler ) {
        throw new InvalidArgumentException("Invalid unmarshaller for property '$property': must be callable.");
      }

      // remove null values from data
      if ($value === null) {
        unset( $data[$property] );
      }
    }
    return $data;
  }


}
