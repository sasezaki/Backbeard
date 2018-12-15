<?php

declare(strict_types=1);

namespace Backbeard\Router;

interface StringRouterInterface
{
    public function match($route, $uri) : ?array;
}
