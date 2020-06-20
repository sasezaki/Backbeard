<?php
namespace Backbeard\View\Templating;

use Backbeard\Dispatcher;
use Backbeard\View\Templating\SfpStreamView;
use Backbeard\View\ViewModel;

use Backbeard\Router\StringRouter;
use Backbeard\Router\ArrayRouter;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;
use SfpStreamView\View as BaseStreamView;

use PHPUnit\Framework\TestCase;

class TemplatingViewTest extends TestCase
{
    private $responseFactory;
    private $view;
    private $stringRouter;
    private $arrayRouter;

    public function setUp() : void
    {
        $this->responseFactory = new ResponseFactory();
        $this->view = new SfpStreamView(new BaseStreamView(__DIR__.'/../../_files/views'), $this->responseFactory);
        $this->stringRouter = $stringRouter = new StringRouter(new \FastRoute\RouteParser\Std());
        $this->arrayRouter = new ArrayRouter($stringRouter);
    }

    public function testActionReturnIsViewModel()
    {
        $request = ServerRequestFactory::fromGlobals();

        $dispatcher = new Dispatcher(call_user_func(function () {
            yield function () {
                return true;
            } => function () {
                return new ViewModel(['key' => 'foo'], '/test.phtml');
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher->dispatch($request);
        $this->assertSame('foo', (string) $response->getBody());
    }

    public function testRoutenameWouldbeResolveAsTemplateName()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/test'));
        $dispatcher = new Dispatcher(call_user_func(function () {
            yield '/test' => function () {
                return ['key' => 'var'];
            };
        }), $this->view, $this->stringRouter, $this->arrayRouter, $this->responseFactory);

        $response = $dispatcher->dispatch($request);
        $this->assertSame('var', (string) $response->getBody());
    }
}
