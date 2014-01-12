<?php
namespace BackbeardTest;
use Backbeard\Dispatcher;
use Zend\Stdlib\ReqeuestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatcherShouldBeZF2DispatcherCompatible()
    {
        $request = $this->getMock('Zend\Stdlib\RequestInterface');
        $this->assertInstanceof('Zend\Stdlib\ResponseInterface', (new Dispatcher([]))->dispatch($request));
    }

    public function testRoutingKeyHandlingString()
    {
        $request = $this->getMock('\Zend\Stdlib\RequestInterface');
        $response = (new Dispatcher(['/' => function(){return 'hello';}]))->dispatch($request);
        $this->assertEmpty($response->getContent());

        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/');
        $response = (new Dispatcher(['/' => function(){return 'hello';}]))->dispatch($request);
        $this->assertSame('hello', $response->getContent());

        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/foo/bar');
        $response = (new Dispatcher(['/foo/:bar' => function($bar){return $bar;}]))->dispatch($request);
        $this->assertSame('bar', $response->getContent());
    }

    public function testRoutingKeyHandlingArray()
    {
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setUri('/foo/bar');
        $response = (new Dispatcher(call_user_func(function() {
            yield ['route' => '/foo/:bar'] => function($bar){return $bar;};
        })))->dispatch($request);
        $this->assertSame('bar', $response->getContent());
    }
}