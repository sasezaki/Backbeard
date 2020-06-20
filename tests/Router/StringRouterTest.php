<?php

namespace BackbeardTest\Router;

use Backbeard\Router\StringRouter;
use PHPUnit\Framework\TestCase;

class StringRouterTest extends TestCase
{
    public function testBuildRegexWillFailWhenSamePlaceFolder()
    {
        $this->expectException(\LogicException::class);
        $router = new StringRouter(new \FastRoute\RouteParser\Std());
        $router->match('/foo/{id:[0-9]}/{id:[0-9]}', '/foo/bar');
    }
}
