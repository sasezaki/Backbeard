<?php

namespace BackbeardTest;

use Backbeard\ClosureActionScope;
use Backbeard\Dispatcher;
use Backbeard\ActionContinueInterface;
use Backbeard\ValidationError;
use Backbeard\Router\StringRouter;
use Backbeard\Router\ArrayRouter;
use Backbeard\RoutingResult;
use Backbeard\View\ViewInterface;
use Backbeard\View\ViewModelInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DispatcherTest extends TestCase
{
    /** @var ViewInterface|MockObject */
    private $view;
    private $stringRouter;
    private $arrayRouter;

    /** @var ServerRequestFactoryInterface */
    private $requestFactory;

    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function setUp() : void
    {
        $this->view = $this->getViewMock();
        $this->stringRouter = $stringRouter = new StringRouter(new \FastRoute\RouteParser\Std());
        $this->arrayRouter = new ArrayRouter($stringRouter);

        $this->requestFactory = new \Laminas\Diactoros\ServerRequestFactory();
        $this->uriFactory = new \Laminas\Diactoros\UriFactory;
        $this->responseFactory = new \Laminas\Diactoros\ResponseFactory;
    }

    private function getViewMock() : MockObject
    {
        $mockBuilder = $this->getMockBuilder(ViewInterface::class);
        $mockBuilder->setMethods(['marshalViewModel', 'marshalResponse']);
        $view = $mockBuilder->getMock();

        $view->method('marshalViewModel')
            ->willReturn($this->createMock(ViewModelInterface::class));

        $view->method('marshalResponse')
            ->willReturn($this->createMock(ResponseInterface::class));

        return $view;
    }

    public function testReturnNullWhenNotMatched()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/', []);
        $dispatcher = new Dispatcher((function () {
            yield function () {
                return false;
            } => function () {
            };
        })(), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $response = $dispatcher->dispatch($request);
        $this->assertNull($response);
    }

    public function testActionScope()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $actionScopeResult = [];
        $dispatcher = new Dispatcher(call_user_func(function () use (&$actionScopeResult) {
            yield function () {
                return true;
            } => function () use (&$actionScopeResult) {
                /** @var ClosureActionScope $this */
                $actionScopeResult['request'] = $this->getRequest();
                $actionScopeResult['responseFactory'] = $this->getResponseFactory();

                return $this->getResponseFactory()->createResponse();
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $response = $dispatcher->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(ServerRequestInterface::class, $actionScopeResult['request']);
        $this->assertInstanceOf(ResponseFactoryInterface::class, $actionScopeResult['responseFactory']);
    }

    public function testRoutingStringHandleAsRoute()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $request = $request->withUri($this->uriFactory->createUri('http://example.com/foo/5'));

        $routing = (function () {
            yield '/foo/{id:[0-9]}' => function ($id) {
                return (string) $id;
            };
            yield '/bar/{id:[0-9]}' => function ($id) {
                return (string) $id;
            };
        })();

        $dispatcher = new Dispatcher($routing, $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('5', (string) $response->getBody());

        // not matched
        $request = $this->requestFactory->createServerRequest('GET', '/')->withUri($this->uriFactory->createUri('http://example.com/bar/6'));
        $response = $dispatcher->dispatch($request);
        $this->assertSame('6', (string) $response->getBody());
    }

    public function testRoutingKeyHandleStringAsPath()
    {
        /** @var ServerRequestInterface $request */
        $request = $this->requestFactory->createServerRequest('GET', '/');

        $gen = function () {
            yield ['GET' => '/foo'] => function () {
                return 'hello';
            };
        };

        $dispatcher1 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $dispatcher2 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $dispatcher3 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher1->dispatch($request);
        $this->assertNull($response);

        $request = $request->withUri($this->uriFactory->createUri()->withPath('/foo'))->withMethod('GET');
        $response = $dispatcher2->dispatch($request);
        $this->assertSame('hello', (string) $response->getBody());

        $request = $request->withUri($this->uriFactory->createUri()->withPath('/foo'))->withMethod('POST');
        $response = $dispatcher3->dispatch($request);
        $this->assertNull($response);

        $gen = function () {
            yield ['GET' => '/foo',
                   'Header' => [
                       'User-Agent' => function ($headers) {
                        if (! empty($headers) && strpos(current($headers), 'Mozilla') === 0) {
                            return true;
                        }
                       },
                   ],
            ] => function () {
                return 'hello';
            };
        };

        $dispatcher4 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $dispatcher5 = new Dispatcher($gen(), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $request = $request->withUri($this->uriFactory->createUri()->withPath('/foo'))->withMethod('GET');
        $response = $dispatcher4->dispatch($request);
        $this->assertNull($response);

        $request = $request->withUri($this->uriFactory->createUri()->withPath('/foo'))->withMethod('GET');
        $request = $request->withHeader('User-Agent', 'Mozilla/5.0');
        $response = $dispatcher5->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRoutingKeyHandleClosureAsMatcher()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return true;
            } => function () {
                return 'bar';
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $response = $dispatcher->dispatch($request);
        $this->assertSame('bar', (string) $response->getBody());
    }

    public function testRouterResultArrayShouldPassAction()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return ['var1', 'var2'];
            } => function ($var1, $var2) {
                return $var1.$var2;
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $response = $dispatcher->dispatch($request);
        $this->assertSame('var1var2', (string) $response->getBody());
    }

    public function testRouterResultRouteMatchShouldPassAction()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return new RoutingResult(true, ['var1' => 'var1', 'var2' => 'var2']);
            } => function ($var1, $var2) {
                return $var1.$var2;
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);
        $response = $dispatcher->dispatch($request);
        $this->assertSame('var1var2', (string) $response->getBody());
    }

    public function testRoutingKeyHandleUnexpected() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $request = $this->requestFactory->createServerRequest('GET', '/');
        (new Dispatcher(call_user_func(function () {
            yield null => function () {
                return 'bar';
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory))->dispatch($request);
    }

    public function testActionOfUndefinedTypeShouldRaiseTypeError() : void
    {
        $this->expectException(\TypeError::class);

        $request = $this->requestFactory->createServerRequest('GET', '/');

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return true;
            } => 'string';
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $dispatcher->dispatch($request);
    }

    public function testActionReturnIsIntUsedAsStatusCode()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return true;
            } => function () {
                /** @var ClosureActionScope $this */
                $response = $this->getResponseFactory()->createResponse();
                return $response->withStatus(503);
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher->dispatch($request);
        $this->assertSame(503, $response->getStatusCode());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testActionReturnUnkown()
    {
        $this->expectException(\RuntimeException::class);

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return true;
            } => function () {
                /** @var ClosureActionScope $this */
                $response = $this->getResponseFactory()->createResponse();
                $response->getBody()->write('a');

                return true; // treat just as succes instead of false;
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $dispatcher->dispatch($request);
    }

    public function testContinueWhenActionReturnIsFalse()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write('oh ');

        $responseFactory = new class ($response) implements ResponseFactoryInterface {
            private $response;
            public function __construct($response)
            {
                $this->response = $response;
            }
            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return $this->response;
            }
        };

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return true;
            } => function () {
                return false;
            };
            yield function () {
                return true;
            } => function () {
                return 'matched';
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $responseFactory);

        $response = $dispatcher->dispatch($request);
        $this->assertSame('oh matched', (string) $response->getBody());
    }

    public function testContinueWhenActionReturnHasActionContinueInterface()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');

        $actionContinue = $this->createMock(ActionContinueInterface::class);
        $dispatcher = new Dispatcher(call_user_func(function () use ($actionContinue) {
            yield function () {
                return true;
            } => function () use ($actionContinue) {
                return $actionContinue;
            };
            yield function () {
                return true;
            } => function () {
                return 'bar';
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher->dispatch($request);

        $this->assertSame('bar', (string) $response->getBody());
    }

    public function testValidationErrorShouldContinueRoutingAndHasError()
    {
        $request = $this->requestFactory->createServerRequest('POST', '/entry');

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
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher->dispatch($request);

        $this->assertSame('foo', (string) $response->getBody());
    }

    public function testValidationThroughWhenNotMatchedRouting()
    {
        $request = $this->requestFactory->createServerRequest('GET', '/entry');

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
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher->dispatch($request);
        $this->assertSame('error is NULL', (string) $response->getBody());
    }
}
