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
   * @param \lib\models\BaseModel  $model
   * @param int $width The default width of the form in pixel (defaults to 300)
   * @throws \Exception
   * @throws \InvalidArgumentException
   * @return array
   */
  public static function createFromModel( \lib\models\BaseModel $model, $width = 300)
  {
    $modelFormData = $model->formData();
    if (! is_array( $modelFormData) or ! count( $modelFormData ) ) {
      throw new \Exception( "No form data exists.");
    }
    $formData = array();
    foreach ($modelFormData as $name => $data) {

      // dynamically get element data from the object
      if ( isset( $data['delegate'] )) {
        foreach ($data['delegate'] as $key => $delegateMethod) {
          $data[$key] = $model->$delegateMethod( $name, $key, $data );
        }
        unset( $data['delegate'] );
      }

      // type
      if (! isset( $data['type'] )) {
        $data['type']  = "TextField";
      }

      // width
      if (! isset( $data['width'] )) {
        $data['width'] = $width;
      }

      // get value from model or default value
      if (! isset( $data['value'] )) {
        $data['value'] = $model->$name;
      }
      if (isset( $data['default'] )) {
        if (! $data['value']) {
          $data['value'] = $data['default'];
        }
        unset( $data['default'] );
      }

      // marshal value
      if (isset( $data['marshaler']['marshal'] )) {
        $marshaler = $data[$property]['marshaler']['marshal'];
        if( ! is_callable( $marshaler ) ){
          throw new \InvalidArgumentException("$name.marshaler must be callable.");
        }
        $data['value'] = $marshaler($data['value']);
        unset( $data['marshaler'] );
      }
      $formData[ $name ] = $data;
    }
    return $formData;
  }

  /**
   * Parses data returned by  dialog.Form widget based on a model
   * @param \lib\models\BaseModel $model
   * @param object $data;
   * @throws \Exception
   * @throws \InvalidArgumentException
   * @return array
   */
  protected static function parseResultData(\lib\models\BaseModel $model, $data)
  {
    $data = json_decode(json_encode( $data ),true);
    $formData = $model->formData();
    if (! is_array( $formData) or ! count( $formData ) ) {
      throw new \InvalidArgumentException( 'Model has no valid form data.');
    }
    foreach ($data as $property => $value) {

      // is it an editable property?
      if (! isset( $formData[$property] )) {
        throw new \InvalidArgumentException( "Invalid form data property '$property'");
      }

      // should I ignore it?
      if (isset( $formData[$property]['ignore'] ) and $formData[$property]['ignore'] === true) {
        unset( $data[$property] );
        continue;
      }

      // marshaler
      if (isset( $formData[$property]['marshaler']['unmarshal'] )) {
        $unmarshaler = $formData[$property]['marshaler']['unmarshal'];
        if( ! is_callable( $unmarshaler ) ){
          throw new \InvalidArgumentException("Invalid unmarshaller for property '$property': must be callable.");
        }
        $data['value'] = $unmarshaler($data['value']);
      }

      // remove null values from data
      if ($value === null) {
        unset( $data[$property] );
      }
    }
    return $data;
  }
}
