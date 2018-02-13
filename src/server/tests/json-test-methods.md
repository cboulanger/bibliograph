# API tester methods

from  

- https://codeception.com/docs/10-WebServices
- https://codeception.com/docs/07-AdvancedUsage 

## JSON result value checking
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

# JSON result type checking

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


## Sending example data

What if you want to execute the same test scenario with different data? In this case you can inject examples as \Codeception\Example instances. Data is defined via the @example annotation, using JSON or Doctrine-style notation (limited to a single line). Doctrine-style:

```php
 /**
  * @example ["/api/", 200]
  * @example ["/api/protected", 401]
  * @example ["/api/not-found-url", 404]
  * @example ["/api/faulty", 500]
  */
  public function checkEndpoints(ApiTester $I, \Codeception\Example $example)
  {
    $I->sendGET($example[0]);
    $I->seeResponseCodeIs($example[1]);
  }

 /**
  * @example { "url": "/", "title": "Welcome" }
  * @example { "url": "/info", "title": "Info" }
  * @example { "url": "/about", "title": "About Us" }
  * @example { "url": "/contact", "title": "Contact Us" }
  */
  public function staticPages(AcceptanceTester $I, \Codeception\Example $example)
  {
    $I->amOnPage($example['url']);
    $I->see($example['title'], 'h1');
    $I->seeInTitle($example['title']);
  }
```
You can also use the @dataprovider annotation for creating dynamic examples, using a protected method for providing example data:

```php
/**
* @dataprovider pageProvider
*/
public function staticPages(AcceptanceTester $I, \Codeception\Example $example)
{
    $I->amOnPage($example['url']);
    $I->see($example['title'], 'h1');
    $I->seeInTitle($example['title']);
}

/**
  * @return array
  */
protected function pageProvider() // alternatively, if you want the function to be public, be sure to prefix it with `_`
{
    return [
        ['url'=>"/", 'title'=>"Welcome"],
        ['url'=>"/info", 'title'=>"Info"],
        ['url'=>"/about", 'title'=>"About Us"],
        ['url'=>"/contact", 'title'=>"Contact Us"]
    ];
}
```