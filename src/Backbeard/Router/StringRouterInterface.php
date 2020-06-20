<?php

declare(strict_types=1);

namespace Backbeard\Router;

interface StringRouterInterface
{
    /** @return null|array<string, string> */
    public function match($route, $uri) : ?array;
}
