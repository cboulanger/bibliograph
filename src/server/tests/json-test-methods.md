# API tester methods

from https://codeception.com/docs/10-WebServices

```php
// matches {"result":"ok"}'
$I->seeResponseContainsJson(['result' => 'ok']);
// it can match tree-like structures as well
$I->seeResponseContainsJson([
  'user' => [
      'name' => 'davert',
      'email' => 'davert@codeception.com',
      'status' => 'inactive'
  ]
]);
```

```php
namespace Helper;

class Api extends \Codeception\Module
{
  public function seeResponseIsHtml()
  {
    $response = $this->getModule('REST')->response;
    $this->assertRegExp('~^<!DOCTYPE HTML(.*?)<html>.*?<\/html>~m', $response);
  }
}
```

```php
<?php
$I->sendGET('/users/1');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'id' => 'integer',
    'name' => 'string',
    'email' => 'string:email',
    'homepage' => 'string:url|null',
    'created_at' => 'string:date',
    'is_active' => 'boolean'
]);
```

More detailed check can be applied if you need to validate the type of fields in a response. You can do that by using with a [seeResponseMatchesJsonType](https://codeception.com/docs/modules/REST#seeResponseMatchesJsonType) action in which you define the structure of JSON response.

```php
$I->sendGET('/users/1');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'id' => 'integer',
    'name' => 'string',
    'email' => 'string:email',
    'homepage' => 'string:url|null',
    'created_at' => 'string:date',
    'is_active' => 'boolean'
]);
```
