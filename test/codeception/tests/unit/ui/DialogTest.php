<?php

namespace tests\unit\models;

use lib\dialog\{Alert, Confirm, Error, Form, Popup, Progress, Prompt, RemoteWizard, Select, Wizard};

class DialogTest extends \tests\unit\Base
{
  public function testCreateAllDialogs()
  {
    (new Alert())->setMessage("Hello!")->show();
    (new Error())->setMessage("Error!")->show();
    (new Confirm())
      ->setMessage("Are you sure?")
      ->setYesButtonLabel("Ja")
      ->setNoButtonLabel("Nein")
      ->setService("service")
      ->setMethod("method")
      ->setParams(["doo","gar"])
      ->show();
    (new Form())
      ->setMessage("Please enter your user information")
      ->setFormData(['username' => [
          'type'  => "TextField",
          'label' => "User Name",
          'value' => ""
        ]])
      ->setAllowCancel(true)
      ->setService("service")
      ->setMethod("method")
      ->show();
    (new Popup())
      ->setMessage("Backup completed")
      ->show();
    (new Select())
      ->setMessage("Which fruit do you like best?")
      ->setOptions(["apples","pears","bananas"])
      ->setAllowCancel(false)
      ->show();
    (new Progress())
      ->setProgress(50)
      ->setService("service")
      ->setMethod("method")
      ->setParams(["param1","param2"])
      ->show();
    (new Prompt())
      ->setMessage("Please enter the name of the test dir")
      ->setDefault("test")
      ->setAutoSubmitTimeout(10)
      ->setRequireInput(true)
      ->show();
    (new Wizard())
      ->setPageData([])
      ->setAllowCancel(true)
      ->setRoute("service.method")
      ->show();
    (new RemoteWizard)
      ->setPageData([])
      ->setPage(3)
      ->setAllowCancel(false)
      ->setAllowFinish(false)
      ->setRoute("service.method")
      ->show();
    //$expected = '[{"name":"dialog","data":{"type":"alert","properties":{"message":"This is an alert"},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"confirm","properties":{"message":"Are you sure?","yesButtonLabel":"yes","noButtonLabel":"no","allowCancel":false},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"form","properties":{"message":"Please enter your user information","formData":[],"allowCancel":false,"width":300},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"popup","properties":{"message":"Backup completed"},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"progress","properties":[],"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"prompt","properties":{"message":"Please enter the name of the test dir","value":"test","requireInput":true},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"remoteWizard","properties":{"serviceName":"service","serviceMethod":"method","pageData":[],"page":0,"allowCancel":false,"allowFinish":false}}},{"name":"dialog","data":{"type":"select","properties":{"message":"Which fruit do you like best?","options":["apples","pears","bananas"],"allowCancel":false},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"wizard","properties":{"pageData":[],"allowCancel":false},"service":"service","method":"method","params":["param1","param2"]}}]';
    //$this->assertEquals( Yii::$app->eventQueue->toArray(), json_decode($expected, true) );
  }
}
