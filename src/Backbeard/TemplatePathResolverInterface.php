<?php
namespace Backbeard;

interface TemplatePathResolverInterface
{
    public function resolve(RouteMatch $routeMatch);
}
