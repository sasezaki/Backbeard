<?php

namespace Backbeard\View\Templating;

use Backbeard\RoutingResult;

interface TemplatePathResolverInterface
{
    public function resolve(RoutingResult $routingResult);
}
