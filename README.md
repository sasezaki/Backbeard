Backbeard
==========

Backbeard is yet another DSLish minimum oriented framework for PHP.

[![Build Status](https://travis-ci.org/sasezaki/Backbeard.png?branch=master)](https://travis-ci.org/sasezaki/Backbeard)
[![Coverage Status](https://coveralls.io/repos/sasezaki/Backbeard/badge.png)](https://coveralls.io/r/sasezaki/Backbeard)

![backbeard](http://gyazo.com/44a5c43a817927032d6f5ff0ed8cda74.png)

## Principle
`yield function(Request $request){return $matched;} => function(){return $response;};`

## Usage

```php
<?php
use Backbeard\Dispatcher;
use Backbeard\ValidationError;

$routingFactory = function ($serviceLocator) {
    yield '/hello' => 'hello';

    $error = (yield ['POST' => '/entry/{id:[0-9]}'] => function ($id) {
        if ($this->getRequest()->getPost()['NAME'] == 'wtf') {
            return ['var1' => 'baz']; // will be render entry.phtml
        } else {
            return new ValidationError(['error']);
        }
    });

    yield '/entry/{id:[0-9]}' => function ($id) use ($error) {
        $message = $error ? htmlspecialchars(current($error->getMessages())) :'';
        return "Hello $id ".$message.
        '<form method="POST" action="/entry/'.$id.'">'.
            'NAME<input type="text" name="NAME">'.
        '</form>';
    };

    yield [
      'GET' => '/foo',
      'Header' => [
        'User-Agent' => function($headers){
          if (!empty($headers) && strpos(current($headers), 'Mozilla') === 0) {
            return true;
          }
        }
      ]
    ] => function(){return 'hello Mozilla';};
};

(new Dispatcher($routingFactory($serviceLocator)))->dispatch(new Request, new Response);
```

## Install 
There is several way.

### Using as component 
 - `php composer.phar require sasezaki/backbeard dev-master`

### Using Application Skeleton
https://github.com/sasezaki/BackbeardSkeleton/

 - `php composer.phar create-project -s dev sasezaki/backbeard-skeleton path/to/install`

    When install finished, you can try running with php built-in web server 
 - `php -S localhost:8080 -t public/ public/index.php`

## NOTES
THIS PROJECT IS A PROOF OF CONCEPT FOR GENERATOR BASED ROUTER,
AND NOT INTENDED FOR PRODUCTION USE.
PLEASE USE AT YOUR OWN RISK.
