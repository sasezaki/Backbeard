<?php

namespace BackbeardTest;

use Backbeard\Dispatcher;
use Backbeard\ActionContinueInterface;
use Backbeard\ValidationError;
use Backbeard\View;
use Backbeard\Router;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Phly\Http\ServerRequestFactory;
use Phly\Http\Response;
use Phly\Http\Uri;
use Backbeard\RouteMatch;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    private $view;
    private $router;

    public function setUp()
    {
        $this->view = new View(__DIR__.'/_files/views');
        $this->router = new Router(new \FastRoute\RouteParser\Std());
    }

    /**
     * @expectedException \LogicException
     */
    public function testReturnFalseWhenNotMatched()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return false;} => function () {};
        }), $this->view, $this->router);
        $result = $dispatcher->dispatch($request, $response);
        $this->assertFalse($result);
        $dispatcher->getActionResponse();
    }
    
    public function testActionScope()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $actionScopeResult = [];
        $dispatcher = new Dispatcher(call_user_func(function () use (&$actionScopeResult) {
            yield function () {return true;} => function () use (&$actionScopeResult) {
                $actionScopeResult['request'] = $this->getRequest();
                $actionScopeResult['response'] = $this->getResponse();
            };
        }), $this->view, $this->router);
        $result = $dispatcher->dispatch($request, $response);
        $this->assertTrue($result);
        var_dump($actionScopeResult);
        $this->assertTrue($actionScopeResult['request'] instanceof ServerRequestInterface);
        $this->assertTrue($actionScopeResult['response'] instanceof ResponseInterface);
    }

    public function testRoutingStringHandleAsRoute()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('http://example.com/foo/5'));
        $response = new Response();

        $routing = call_user_func(function () {
            yield '/foo/{id:[0-9]}' => function ($id) {
                return (string) $id;
            };
            yield '/bar/{id:[0-9]}' => function ($id) {
                return (string) $id;
            };
        });

        $dispatcher = new Dispatcher($routing, $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);

        $this->assertTrue($result);
        $response = $dispatcher->getActionResponse();
        $this->assertInstanceof(Response::class, $response);
        $this->assertSame('5', (string) $response->getBody());

        // not matched
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('http://example.com/bar/6'));
        $response = new Response();
        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertInstanceof(Response::class, $response);
        $this->assertSame('6', (string) $response->getBody());
    }

    public function testRoutingKeyHandleStringAsPath()
    {
        /** @var Response $request */
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
                
        $gen = function() {
            yield ['GET' => '/foo'] => function(){return 'hello';};
        };
        
        $dispatcher1 = new Dispatcher($gen(), $this->view, $this->router);
        $dispatcher2 = new Dispatcher($gen(), $this->view, $this->router);
        $dispatcher3 = new Dispatcher($gen(), $this->view, $this->router);
        
        $result = $dispatcher1->dispatch($request, $response);
        $this->assertFalse($result);

        $request = $request->withUri((new Uri)->withPath('/foo'))->withMethod('GET');
        $result = $dispatcher2->dispatch($request, $response);
        $this->assertTrue($result);
        $this->assertSame('hello', (string) $response->getBody());

        $request = $request->withUri((new Uri)->withPath('/foo'))->withMethod('POST');
        $result = $dispatcher3->dispatch($request, $response);
        $this->assertFalse($result);
        
        $gen = function() {
            yield ['GET' => '/foo',
                   'Header' => [
                       'User-Agent' => function($headers){
                           if (!empty($headers) && strpos(current($headers), 'Mozilla') === 0) {
                               return true;
                           }
                       }
                   ]
            ] => function(){return 'hello';};
        };
        
        $dispatcher4 = new Dispatcher($gen(), $this->view, $this->router);
        $dispatcher5 = new Dispatcher($gen(), $this->view, $this->router);
        
        
        $request = $request->withUri((new Uri)->withPath('/foo'))->withMethod('GET');
        $result = $dispatcher4->dispatch($request, $response);
        $this->assertFalse($result);
        
        $request = $request->withUri((new Uri)->withPath('/foo'))->withMethod('GET');
        $request = $request->withHeader('User-Agent', 'Mozilla/5.0');
        $result = $dispatcher5->dispatch($request, $response);
        $this->assertTrue($result);
    }

    public function testRoutingKeyHandleClosureAsMatcher()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {return 'bar';};
        }), $this->view, $this->router);
        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();

        $this->assertSame('bar', (string) $response->getBody());
    }

    public function testRouterResultArrayShouldPassAction()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return ['var1', 'var2'];} => function ($var1, $var2) {return $var1.$var2;};
        }), $this->view, $this->router);
        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertSame('var1var2', (string) $response->getBody());
    }

    public function testRouterResultRouteMatchShouldPassAction()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return new RouteMatch(['var1', 'var2']);} => function ($var1, $var2) {return $var1.$var2;};
        }), $this->view, $this->router);
        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertSame('var1var2', (string) $response->getBody());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRoutingKeyHandleUnexpected()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        (new Dispatcher(call_user_func(function () {
            yield null => function () {return 'bar';};
        }), $this->view, $this->router))->dispatch($request, $response);
    }

    public function testActionReturnResponseShouldBeUsed()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {
                $response_new = new Response();

                return $response_new;
            };
        }), $this->view, $this->router);
        $result = $dispatcher->dispatch($request, $response);
        $response2 = $dispatcher->getActionResponse();
        $this->assertNotSame(spl_object_hash($response), spl_object_hash($response2));
    }

    public function testIntActionAsStatusCode()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => 503;
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertSame(503, $response->getStatusCode());
    }

    public function testActionResultIsIntUsedAsStatusCode()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {return 503;};
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertSame(503, $response->getStatusCode());
    }

    public function testActionReturnUnkown()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {
                $this->getResponse()->getBody()->write('a');

                return true; // treat just as succes instead of false;
            };
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertSame('a', (string) $response->getBody());
    }

    public function testRoutenameWouldbeResolveAsTemplateName()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/test'));
        $response = new Response();
        $dispatcher = new Dispatcher(call_user_func(function () {
            yield '/test' => function () {
                return ['key' => 'var'];
            };
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertInstanceof(ResponseInterface::class, $response);
        $this->assertSame('var', (string) $response->getBody());
    }

    public function testContinueWhenActionReturnIsFalse()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/'));
        $response = new Response();
        $response->getBody()->write('oh ');

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {return false;};
            yield function () {return true;} => function () {return 'matched';};
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertSame('oh matched', (string) $response->getBody());
    }

    public function testContinueWhenActionReturnHasActionContinueInterface()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/'));
        $response = new Response();

        $actionContinue = $this->getMock(ActionContinueInterface::class);
        $dispatcher = new Dispatcher(call_user_func(function () use ($actionContinue) {
            yield function () {return true;} => function () use ($actionContinue) {return $actionContinue;};
            yield function () {return true;} => function () {return 'bar';};
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();

        $this->assertSame('bar', (string) $response->getBody());
    }

    public function testValidationErrorShouldContinueRoutingAndHasError()
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'POST',
        ])->withUri(new Uri('/entry'));
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            /* @var ServerRequestInterface $request */
            $error = (yield function (ServerRequestInterface $request) {
                return $request->getMethod() === 'POST' &&
                    $request->getUri()->getPath() === '/entry';
            } => function () {
                return new ValidationError(['foo']);
            });
            yield '/entry' => function () use ($error) {
                return current($error->getMessages());
            };
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();

        $this->assertSame('foo', (string) $response->getBody());
    }

    public function testValidationThroughWhenNotMatchedRouting()
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
        ])->withUri(new Uri('/entry'));
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            $error = (yield function (ServerRequestInterface $request) {
                return $request->getMethod() === 'POST' &&
                $request->getUri()->getPath() === '/entry';
            } => function () {
                return new ValidationError(['foo']);
            });
            yield '/entry' => function () use ($error) {
                return 'error is '.gettype($error);
            };
        }), $this->view, $this->router);

        $result = $dispatcher->dispatch($request, $response);
        $response = $dispatcher->getActionResponse();
        $this->assertSame('error is NULL', (string) $response->getBody());
    }
}
