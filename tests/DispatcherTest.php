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

//     public function testRoutingKeyHandleStringAsPath()
//     {
//         $request = ServerRequestFactory::fromGlobals();
//         $response = new Response();

//         $response = (new Dispatcher(call_user_func(function() {
//             yield ['route' => '/'] => function(){return 'hello';};
//         }), $this->view, $this->router))->dispatch($request, $response);

//         $this->assertEmpty($response->getBody()->getContents());

//         $request = $request->withUri((new Uri)->withPath('/'));
//         $response = (new Dispatcher(call_user_func(function() {
//             yield ['route' => '/'] => function(){return 'hello';};
//         }), $this->view, $this->router))->dispatch($request, $response);
//         $this->assertSame('hello', $response->getBody()->getContents());

//         $request = $request->withUri((new Uri)->withPath('/foo/bar'));
//         $response = (new Dispatcher(call_user_func(function() {
//             yield ['route' => '/foo/:bar'] => function($bar){return $bar;};
//         }), $this->view, $this->router))->dispatch($request, $response);
//         $this->assertSame('bar', $response->getBody()->getContents());
//     }

//     public function testRoutingKeyHandleArrayAsRequestContext()
//     {
//         $request = new \Zend\Http\PhpEnvironment\Request;
//         $request->setUri('/foo/bar');
//         $response = (new Dispatcher(call_user_func(function() {
//             yield ['route' => '/foo/:bar'] => function($bar){return $bar;};
//         })))->dispatch($request);
//         $this->assertSame('bar', $response->getContent());
//         $dispatcher = (new Dispatcher(call_user_func(function() {
//             yield ['method' => 'POST', 'route' => '/foo/:bar'] => function($bar){return $bar;};
//         })));
//         $this->assertEmpty($dispatcher->dispatch($request)->getContent());
//         $dispatcher = (new Dispatcher(call_user_func(function() {
//             yield ['method' => 'POST', 'route' => '/foo/:bar'] => function($bar){return $bar;};
//         })));
//         $request->setMethod('POST');
//         $this->assertSame('bar', $dispatcher->dispatch($request)->getContent());
//     }

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
                $this->getBody()->write('a');

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
