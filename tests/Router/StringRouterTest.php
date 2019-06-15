<?php

namespace BackbeardTest\Router;

use Backbeard\Router\StringRouter;
use PHPUnit\Framework\TestCase;

class StringRouterTest extends TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testBuildRegexWillFailWhenSamePlaceFolder()
    {
        $router = new StringRouter(new \FastRoute\RouteParser\Std());
        $router->match('/foo/{id:[0-9]}/{id:[0-9]}', '/foo/bar');
    }
}
