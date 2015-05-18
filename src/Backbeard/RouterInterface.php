<?php

namespace Backbeard;

interface RouterInterface
{
    /**
     * @param string $route
     * @param string $uri
     *
     * @return array|false
     */
    public function match($route, $uri);
}
