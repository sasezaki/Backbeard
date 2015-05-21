<?php

namespace BackbeardTest;

use Backbeard\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testBuildRegexWillFailWhenSamePlaceFolder()
    {
        $router = new Router(new \FastRoute\RouteParser\Std());
        $router->match('/foo/{id:[0-9]}/{id:[0-9]}', '/foo/bar');
    }
}
