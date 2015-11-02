<?php

namespace Backbeard\Router;

interface StringRouterInterface
{
    /**
     * @param string $route
     * @param string $uri
     *
     * @return array|false
     */
    public function match($route, $uri);
}
