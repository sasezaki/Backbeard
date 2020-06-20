<?php

declare(strict_types=1);

namespace Backbeard\Router;

use Psr\Http\Message\ServerRequestInterface as Request;

class ArrayRouter implements ArrayRouterInterface
{
    private StringRouterInterface $stringRouter;

    public function __construct(StringRouterInterface $stringRouter)
    {
        $this->stringRouter = $stringRouter;
    }

    public function match(array $route, Request $request) : ?array
    {
        $httpMethod = key($route);
        $stringRoute = current($route);

        if (in_array($httpMethod, ['GET', 'POST', 'PUT', 'DELETE'])) {
            if ($httpMethod !== $request->getMethod()) {
                return null;
            }
        }

        $match = $this->stringRouter->match($stringRoute, $request->getUri()->getPath());

        if ($match === null) {
            return null;
        }

        array_shift($route);

        foreach ($route as $method => $rules) {
            $getter = 'get'.ucfirst($method);
            foreach ($rules as $param => $callback) {
                $value = call_user_func([$request, $getter], $param);
                if (! $callback($value)) {
                    return null;
                }
            }
        }

        return $match;
    }
}
