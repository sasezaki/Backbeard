<?php

namespace BackbeardTest;

use Backbeard\Dispatcher;
use Backbeard\ActionContinueInterface;
use Backbeard\ValidationError;
use Backbeard\View\Templating\SfpStreamView;
use Backbeard\Router\StringRouter;
use Backbeard\RoutingResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use SfpStreamView\View as BaseStreamView;
use Backbeard\View\ViewModel;
use Backbeard\Router\ArrayRouter;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    private $view;
    private $stringRouter;
    private $arrayRouter;

    public function setUp()
    {
        $this->view = new SfpStreamView(new BaseStreamView(__DIR__.'/_files/views'));
        $this->stringRouter = $stringRouter = new StringRouter(new \FastRoute\RouteParser\Std());
        $this->arrayRouter = new ArrayRouter($stringRouter);
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
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        $result = $dispatcher->dispatch($request, $response);
        $this->assertFalse($result->isDispatched());
        $result->getResponse();
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
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        $result = $dispatcher->dispatch($request, $response);
        $this->assertTrue($result->isDispatched());
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

        $dispatcher = new Dispatcher($routing, $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);

        $this->assertTrue($result->isDispatched());
        $response = $result->getResponse();
        $this->assertInstanceof(Response::class, $response);
        $this->assertSame('5', (string) $response->getBody());

        // not matched
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('http://example.com/bar/6'));
        $response = new Response();
        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
        $this->assertInstanceof(Response::class, $response);
        $this->assertSame('6', (string) $response->getBody());
    }

    public function testRoutingKeyHandleStringAsPath()
    {
        /** @var Response $request */
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $gen = function () {
            yield ['GET' => '/foo'] => function () {return 'hello';};
        };

        $dispatcher1 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter);
        $dispatcher2 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter);
        $dispatcher3 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher1->dispatch($request, $response);
        $this->assertFalse($result->isDispatched());

        $request = $request->withUri((new Uri())->withPath('/foo'))->withMethod('GET');
        $result = $dispatcher2->dispatch($request, $response);
        $this->assertTrue($result->isDispatched());
        $this->assertSame('hello', (string) $response->getBody());

        $request = $request->withUri((new Uri())->withPath('/foo'))->withMethod('POST');
        $result = $dispatcher3->dispatch($request, $response);
        $this->assertFalse($result->isDispatched());

        $gen = function () {
            yield ['GET' => '/foo',
                   'Header' => [
                       'User-Agent' => function ($headers) {
                           if (!empty($headers) && strpos(current($headers), 'Mozilla') === 0) {
                               return true;
                           }
                       },
                   ],
            ] => function () {return 'hello';};
        };

        $dispatcher4 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter);
        $dispatcher5 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter);

        $request = $request->withUri((new Uri())->withPath('/foo'))->withMethod('GET');
        $result = $dispatcher4->dispatch($request, $response);
        $this->assertFalse($result->isDispatched());

        $request = $request->withUri((new Uri())->withPath('/foo'))->withMethod('GET');
        $request = $request->withHeader('User-Agent', 'Mozilla/5.0');
        $result = $dispatcher5->dispatch($request, $response);
        $this->assertTrue($result->isDispatched());
    }

    public function testRoutingKeyHandleClosureAsMatcher()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {return 'bar';};
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();

        $this->assertSame('bar', (string) $response->getBody());
    }

    public function testRouterResultArrayShouldPassAction()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return ['var1', 'var2'];} => function ($var1, $var2) {return $var1.$var2;};
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
        $this->assertSame('var1var2', (string) $response->getBody());
    }

    public function testRouterResultRouteMatchShouldPassAction()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return new RoutingResult(true, ['var1', 'var2']);} => function ($var1, $var2) {return $var1.$var2;};
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
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
        }), $this->view, $this->stringRouter))->dispatch($request, $response);
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
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        $result = $dispatcher->dispatch($request, $response);
        $response2 = $result->getResponse();
        $this->assertNotSame(spl_object_hash($response), spl_object_hash($response2));
    }
    
    /**
     * @expectedException \UnexpectedValueException
     */
    public function testActionOfUndefinedTypeShouldRaiseException()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        
        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => 'string';
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        
        $dispatcher->dispatch($request, $response);
    }

    public function testActionReturnIsViewModel()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        
        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {
                return new ViewModel(['key' => 'foo'], '/test.phtml');
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter);
        
        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();        
    }
    
    public function testActionReturnIsIntUsedAsStatusCode()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {return true;} => function () {return $this->getResponse()->withStatus(503);};
        }), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
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
        }), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
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
        }), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
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
        }), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
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
        }), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();

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
        }), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();

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
        }), $this->view, $this->stringRouter, $this->arrayRouter);

        $result = $dispatcher->dispatch($request, $response);
        $response = $result->getResponse();
        $this->assertSame('error is NULL', (string) $response->getBody());
    }
}
