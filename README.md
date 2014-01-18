Backbeard
==========

Backbeard is yet another DSLish minimum oriented framework for PHP.

[![Build Status](https://travis-ci.org/sasezaki/Backbeard.png?branch=master)](https://travis-ci.org/sasezaki/Backbeard)
[![Coverage Status](https://coveralls.io/repos/sasezaki/Backbeard/badge.png)](https://coveralls.io/r/sasezaki/Backbeard)

![backbeard](http://gyazo.com/44a5c43a817927032d6f5ff0ed8cda74.png)

## Principle
`yield $router($request) => $action();`

## Usage

```php
<?php
use Backbeard\Dispatcher;
use Backbeard\ValidationError;
use Zend\Http\PhpEnvironment\Request;

$routing = call_user_func(function () {
    yield '/hello/:foo' => function ($foo) {
        return "Hello $foo";
    };

    $error = (yield ['method' => 'POST', 'route' => '/entry/:id'] => function ($id) {
        if ($this->get('request')->getPost('NAME') == 'wtf') {
            return ['var1' => 'baz']; // will be render entry.mustache
        } else {
            return new ValidationError(['error']);
        }
    });

    yield '/entry/:id' => function ($id) use ($error) {
        $message = $error ? current($error->getMessages()) :'';
        return "Hello $id ".$message.
        '<form method="POST" action="/entry/'.$id.'">'.
            'NAME<input type="text" name="NAME">'.
        '</form>';
    };

    yield function(Request $request) {
        return $request->getRequestUri() === '/';
    } => function () {
        $response = $this->get('response');
        $response->setContent("Hello");
        return $response;
    };
});

(new Dispatcher($routing))->dispatch(new Request)->send();
```

## Install 
There is several way.

### Just use as component 
 - `php composer.phar require sasezaki/backbeard dev-master`

### Use Application Skeleton
https://github.com/sasezaki/BackbeardSkeleton/

 - `php composer.phar create-project -s dev sasezaki/backbeard-skeleton path/to/install`

    When install finished, you can try running with php built-in web server 
 - `php -S localhost:8080 -t public/ public/index.php`

## NOTES
THIS PROJECT IS A PROOF OF CONCEPT FOR GENERATOR BASED ROUTER,
AND NOT INTENDED FOR PRODUCTION USE.
PLEASE USE AT YOUR OWN RISK.
