<?php

declare(strict_types=1);

namespace Backbeard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

interface DispatcherInterface
{
    public function dispatch(Request $request) : ?ResponseInterface;
}
