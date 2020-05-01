<?php

namespace tests\unit\models;

use lib\dialog\{Alert,Confirm,Form,Popup,Progress,Prompt,RemoteWizard,Select,Wizard};

class DialogTest extends \tests\unit\Base
{
  public function testCreateAllDialogs()
  {
    Alert::create(
      "This is an alert",
      "service","method",["param1","param2"]
    );
    Confirm::create(
      "Are you sure?",
      /* choices */ ["yes","no"],
      "service","method",["param1","param2"]
    );
    Form::create(
      "Please enter your user information",
      /* structured form data */[],
      /* allow cancelation */ false,
      "service","method",["param1","param2"]
    );
    Popup::create(
      "Backup completed",
      "service","method",["param1","param2"]
    );
    Progress::create(
      /* widget properties */ [],
      "service","method",["param1","param2"]
    );
    Prompt::create(
      "Please enter the name of the test dir",
      /* default */ "test",
      "service","method",["param1","param2"],
      /* require input */ true,
      /* auto submit timeout in seconds */ null
    );
    RemoteWizard::create(
      /* Structured page data */ [],
      /* the page to open */ 0,
      /* allow cancelation */ false,
      /* allow to finish early */ false,
      "service","method"
    );
    Select::create(
      "Which fruit do you like best?",
      /* options */ ["apples","pears","bananas"],
      /* allow cancelation */ false,
      "service","method",["param1","param2"]
    );
    Wizard::create(
      /* Structured page data */ [],
      /* allow cancelation */ false,
      "service","method",["param1","param2"]
    );

    //$expected = '[{"name":"dialog","data":{"type":"alert","properties":{"message":"This is an alert"},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"confirm","properties":{"message":"Are you sure?","yesButtonLabel":"yes","noButtonLabel":"no","allowCancel":false},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"form","properties":{"message":"Please enter your user information","formData":[],"allowCancel":false,"width":300},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"popup","properties":{"message":"Backup completed"},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"progress","properties":[],"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"prompt","properties":{"message":"Please enter the name of the test dir","value":"test","requireInput":true},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"remoteWizard","properties":{"serviceName":"service","serviceMethod":"method","pageData":[],"page":0,"allowCancel":false,"allowFinish":false}}},{"name":"dialog","data":{"type":"select","properties":{"message":"Which fruit do you like best?","options":["apples","pears","bananas"],"allowCancel":false},"service":"service","method":"method","params":["param1","param2"]}},{"name":"dialog","data":{"type":"wizard","properties":{"pageData":[],"allowCancel":false},"service":"service","method":"method","params":["param1","param2"]}}]';
    //$this->assertEquals( Yii::$app->eventQueue->toArray(), json_decode($expected, true) );
  }
}
