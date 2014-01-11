#Backbeard

Backbeard is yet another DSLish minimum oriented framework for PHP.

```php
<?php
use Zend\Http\PhpEnvironment\Request;

return call_user_func(function () {
    yield '/hello/:foo' => function ($foo) {
        return "Hello $foo";
    };

    yield ['method' => 'GET', 'route' => '/yeah/:foo/:bar'] => function ($foo, $bar) {
        return ['var1' => 'baz'];
    };

    yield function(Request $request) {
        return $request->getRequestUri() === '/';
    } => function () {
        $response = $this->get('response');
        $response->setContent("Hello");
        return $response;
    };
});
```

##Install
install application skeleton via composer

 - `php composer.phar create-project sasezaki/backbeard-skelton path/to/install`

##Run
with php built-in web server 
 - `php -S localhost:8080 -t public/ public/index.php`
