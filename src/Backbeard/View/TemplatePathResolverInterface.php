<?php

namespace Backbeard\View;

use Backbeard\RoutingResult;

interface TemplatePathResolverInterface
{
    public function resolve(RoutingResult $routingResult);
}
