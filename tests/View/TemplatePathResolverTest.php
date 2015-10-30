<?php

namespace BackbeardTest\View;

use Backbeard\View\TemplatePathResolver;
use Backbeard\RouteMatch;

class TemplatePathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testIndexTranslatedIndex()
    {
        $routeMatch = new RouteMatch(array());
        $resolver = new TemplatePathResolver();

        $routeMatch->setMatchedRouteName('/');
        $this->assertSame('/index.phtml', $resolver->resolve($routeMatch));

        $routeMatch->setMatchedRouteName('/foo/bar/');
        $this->assertSame('/foo/bar/index.phtml', $resolver->resolve($routeMatch));
    }

    public function testSuffix()
    {
        $routeMatch = new RouteMatch(array());
        $resolver = new TemplatePathResolver();
        $resolver->setSuffix('.unkown');

        $routeMatch->setMatchedRouteName('/test');
        $this->assertSame('/test.unkown', $resolver->resolve($routeMatch));
    }
}
