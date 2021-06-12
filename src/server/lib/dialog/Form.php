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

/**
 * Class Form
 * @package lib\dialog
 * @property array $formData
 * @property bool $allowCancel
 * @property array $options
 */
class Form extends Dialog
{
  /**
   * @var string
   */
  protected $message = "";

  /**
   * @param $value
   * @return $this
   */
  public function setMessage(string $value){$this->message=$value; return $this;}

  /**
   * Arrray containing the form data. Example (using
   * json instead of native php array):
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
   * @var array
   */
  public $formData = [];

  /**
   * @param array $value
   * @return $this
   */
  public function setFormData(array $value){$this->formData=$value; return $this;}

  /**
   * Whether cancelling of the dialog is allowed
   * @var bool
   */
  public $allowCancel = false;

  /**
   * @param bool $value
   * @return $this
   */
  public function setAllowCancel(bool $value){$this->allowCancel=$value; return $this;}

  /**
   * The width of the form, defaults to 300px
   * @var int
   */
  public $width = 300;

  /**
   * @param int $value
   * @return $this
   */
  public function setWidth(int $value){$this->width=$value; return $this;}


  /**
   * @inheritdoc
   */
  public function sendToClient($properties=[])
  {
    return parent::sendToClient(array_merge($properties,['formData','message','allowCancel','width']));
  }

  /**
   * Returns an event to the client which prompts the user with a form.
   *
   * @param string $message
   *    The message text
   * @param array $formData
   * @param bool $allowCancel
   *    Whether the form can be cancelled
   * @param string $callbackService
   *    Service that will be called when the user clicks on the OK button
   * @param string $callbackMethod
   *    Service method
   * @param array|null $callbackParams
   *    Optional service params
   * @param array|null $options Optional properties of the form widget
   * @deprecated Please use setters instead
   */
  public static function create()
  {
    list(
      $message,
      $formData,
      $allowCancel,
      $callbackService,
      $callbackMethod,
      $callbackParams,
      $options
      ) = array_pad( func_get_args(), 7, null);
    $properties = [
      'message'     => $message,
      'formData'    => $formData,
      'allowCancel' => $allowCancel,
      'width'       => 300
    ];
    if ( is_array($options) ){
      foreach ($options as $key => $value) $properties[$key] = $value;
    }
    static::addToEventQueue( array(
       'type' => "form",
       'properties'  => $properties,
       'service' => $callbackService,
       'method'  => $callbackMethod,
       'params'  => $callbackParams ?? []
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

      // delegate: dynamically get element data from the object
      // the delegate method is called with ( $property, $key, $field )
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
      $marshaler = ArrayHelper::getValue( $modelFormData, [$property,'marshal'], null );
      if(is_callable($marshaler)) {
        $field['value'] = $marshaler($field['value'], $model, $widgetFormData);
      } elseif( $marshaler) {
        throw new InvalidArgumentException("Invalid marshaller property for '$property': must be callable.");
      }
      unset( $field['marshal'] );
      unset( $field['unmarshal'] );

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

      // should I ignore it?
      if ( ArrayHelper::getValue($modelFormData, [$property, 'ignore'],false) ) {
        unset( $data[$property] );
        continue;
      }

      // unmarshal form field values to be stored in a model property
      $unmarshaler = ArrayHelper::getValue( $modelFormData, [$property,'unmarshal'] );
      if (is_callable($unmarshaler)) {
       $data[$property] = $unmarshaler($data[$property], $data);
      } elseif( $unmarshaler ) {
        throw new InvalidArgumentException("Invalid unmarshaller for property '$property': must be callable.");
      }

      // coerce booleans to int
      if( is_bool($data[$property]) ) {
        $data[$property] = (int) $value;
      }
      // remove null values from data
      elseif ($data[$property] === null) {
        unset( $data[$property] );
      }
    }
    return $data;
  }
}
