<?php
namespace Backbeard\View\Templating;

use Backbeard\Dispatcher;
use Backbeard\View\Templating\SfpStreamView;
use Backbeard\View\ViewModel;

use Backbeard\Router\StringRouter;
use Backbeard\Router\ArrayRouter;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use SfpStreamView\View as BaseStreamView;


class TemplatingViewTest extends \PHPUnit_Framework_TestCase
{
    private $view;
    private $stringRouter;
    private $arrayRouter;
    
    public function setUp()
    {
        $this->view = new SfpStreamView(new BaseStreamView(__DIR__.'/../../_files/views'));
        $this->stringRouter = $stringRouter = new StringRouter(new \FastRoute\RouteParser\Std());
        $this->arrayRouter = new ArrayRouter($stringRouter);
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
        $this->assertSame('foo', (string) $response->getBody());        
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
    
}
