<?php

namespace BackbeardTest\View\Templating;

use Backbeard\View\Templating\TemplatePathResolver;
use Backbeard\RoutingResult;
use PHPUnit\Framework\TestCase;

class TemplatePathResolverTest extends TestCase
{
    public function testIndexTranslatedIndex()
    {
        $routeMatch = new RoutingResult(true, []);
        $resolver = new TemplatePathResolver();

        $routeMatch->setMatchedRouteName('/');
        $this->assertSame('/index.phtml', $resolver->resolve($routeMatch));

        $routeMatch->setMatchedRouteName('/foo/bar/');
        $this->assertSame('/foo/bar/index.phtml', $resolver->resolve($routeMatch));
    }

    public function testSuffix()
    {
        $routeMatch = new RoutingResult(true, []);
        $resolver = new TemplatePathResolver();
        $resolver->setSuffix('.unkown');

        $routeMatch->setMatchedRouteName('/test');
        $this->assertSame('/test.unkown', $resolver->resolve($routeMatch));
    }
}
