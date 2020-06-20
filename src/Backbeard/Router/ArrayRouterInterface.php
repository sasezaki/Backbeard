<?php

declare(strict_types=1);

namespace Backbeard\Router;

use Psr\Http\Message\ServerRequestInterface as Request;

interface ArrayRouterInterface
{
    /**
     * @param array<string, mixed> $route
     * @return null|array<string, string>
     */
    public function match(array $route, Request $request) : ?array;
}
