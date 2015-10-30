<?php

namespace Backbeard\View;

use Backbeard\RouteMatch;

interface TemplatePathResolverInterface
{
    public function resolve(RouteMatch $routeMatch);
}
