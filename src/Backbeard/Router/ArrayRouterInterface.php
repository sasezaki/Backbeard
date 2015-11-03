<?php

namespace Backbeard\Router;

use Psr\Http\Message\ServerRequestInterface as Request;

interface ArrayRouterInterface
{
    /**
     * @param array $route
     * @param Request $request
     *
     * @return array|false
     */
    public function match(array $route, Request $request);
}
