<?php
namespace BackbeardTest;
use Backbeard\Dispatcher;
use Backbeard\ValidationError;
use Zend\Stdlib\ReqeuestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;

use Zend\View\View;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplatePathStack;
use Zend\View\Strategy\PhpRendererStrategy;
use Zend\View\Model\ViewModel;

use Zend\ServiceManager\ServiceManager;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    private $serviceLocator;

    public function setUp()
    {
        $sm = new ServiceManager;
        $sm->setFactory('view', function () {
            $view = new View;
            $renderer = new PhpRenderer();
            $resolver = new TemplatePathStack;
            $resolver->setPaths([__DIR__.'/_files/views/']);
            $renderer->setResolver($resolver);

            $view->getEventManager()->attach(new PhpRendererStrategy($renderer));
            return $view;
        });

        $this->serviceLocator = $sm;
    }

    public function testDispatcherShouldBeZF2DispatcherCompatible()
    {
        $request = $this->getMock('Zend\Stdlib\RequestInterface');
        $dispatcher = new Dispatcher(call_user_func(function(){yield '/' => '';}));
        $this->assertInstanceof('Zend\Stdlib\ResponseInterface', $dispatcher->dispatch($request));
    }

    public function testRoutingKeyHandleStringAsPath()
    {
        $request = $this->getMock('\Zend\Stdlib\RequestInterface');
        $response = (new Dispatcher(call_user_func(function() {
            yield ['route' => '/'] => function(){return 'hello';};
        })))->dispatch($request);
        $this->assertEmpty($response->getContent());

        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/');
        $response = (new Dispatcher(call_user_func(function() {
            yield ['route' => '/'] => function(){return 'hello';};
        })))->dispatch($request);
        $this->assertSame('hello', $response->getContent());

        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/foo/bar');
        $response = (new Dispatcher(call_user_func(function() {
            yield ['route' => '/foo/:bar'] => function($bar){return $bar;};
        })))->dispatch($request);
        $this->assertSame('bar', $response->getContent());
    }

    public function testRoutingKeyHandleArrayAsRequestContext()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/foo/bar');

        $response = (new Dispatcher(call_user_func(function() {
            yield ['route' => '/foo/:bar'] => function($bar){return $bar;};
        })))->dispatch($request);
        $this->assertSame('bar', $response->getContent());

        $dispatcher = (new Dispatcher(call_user_func(function() {
            yield ['method' => 'POST', 'route' => '/foo/:bar'] => function($bar){return $bar;};
        })));
        $this->assertEmpty($dispatcher->dispatch($request)->getContent());

        $dispatcher = (new Dispatcher(call_user_func(function() {
            yield ['method' => 'POST', 'route' => '/foo/:bar'] => function($bar){return $bar;};
        })));
        $request->setMethod('POST');
        $this->assertSame('bar', $dispatcher->dispatch($request)->getContent());
    }

    public function testRoutingKeyHandleClosureAsMatcher()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $response = (new Dispatcher(call_user_func(function() {
            yield function(){return true;} => function(){return 'bar';};
        })))->dispatch($request);
        $this->assertSame('bar', $response->getContent());
    }

    public function testRouterResultArrayShouldPassAction()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $response = (new Dispatcher(call_user_func(function() {
            yield function(){return ['var1', 'var2'];} => function($var1, $var2){return $var1.$var2;};
        })))->dispatch($request);
        $this->assertSame('var1var2', $response->getContent());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRoutingKeyHandleUnexpected()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $response = (new Dispatcher(call_user_func(function() {
            yield null => function(){return 'bar';};
        })))->dispatch($request);
    }

    public function testActionResultHandlerShouldReturnResponse()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/test');
        $dispatcher = new Dispatcher(call_user_func(function() {
            yield ['route' => '/test'] => function () {
                return new ViewModel(['key' => 'var']);
            };
        }), $this->serviceLocator);

        $response = $dispatcher->dispatch($request);
        $this->assertInstanceof('Zend\Stdlib\ResponseInterface', $response);

        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/test');
        $response = (new Dispatcher(call_user_func(function() {
            yield ['route' => '/test'] => function () {return $this->get('response');};
        })))->dispatch($request);
        $this->assertInstanceof('Zend\Stdlib\ResponseInterface', $response);
    }

    public function testContinueWhenActionReturnIsFalse()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/');
        $response = (new Dispatcher(call_user_func(function() {
            yield ['route' => '/'] => function(){return false;};
        })))->dispatch($request, (new \Zend\Http\PhpEnvironment\Response)->setContent("not match"));
        $this->assertInstanceof('Zend\Stdlib\ResponseInterface', $response);
        $this->assertSame('not match', $response->getContent());
    }
    
    public function testContinueWhenActionReturnHasActionContinueInterface()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/');
        $actionContinue = $this->getMock('Backbeard\ActionContinueInterface');
        $response = (new Dispatcher(call_user_func(function() use ($actionContinue) {
            yield ['route' => '/'] => function() use ($actionContinue) {return $actionContinue;};
            yield ['route' => '/'] => function(){return 'bar';};
        })))->dispatch($request);
        $this->assertSame('bar', $response->getContent());
    }

    public function testValidationErrorShouldContinueRoutingAndHasError()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/entry');
        $request->setMethod('POST');
        $response = (new Dispatcher(call_user_func(function() {
            $error = (yield ['method' => 'POST', 'route' => '/entry'] => function() {
                return new ValidationError(['foo']);
            });
            yield '/entry'=> function() use ($error) {
                return current($error->getMessages());
            };
        })))->dispatch($request);
        $this->assertSame('foo', $response->getContent());
    }

    public function testValidationThroughWhenNotMatchedRouting()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/entry');
        $request->setMethod('GET');
        $response = (new Dispatcher(call_user_func(function() {
            $error = (yield ['method' => 'POST', 'route' => '/entry'] => function() {
                return new ValidationError(['foo']);
            });
            yield '/entry'=> function() use ($error) {
                return 'bar';
            };
        })))->dispatch($request);
        $this->assertSame('bar', $response->getContent());
    }
}
