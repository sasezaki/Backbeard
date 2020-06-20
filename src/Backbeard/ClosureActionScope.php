<?php

declare(strict_types=1);

namespace Backbeard;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClosureActionScope
{
    private \Psr\Http\Message\ServerRequestInterface $request;
    private \Psr\Http\Message\ResponseFactoryInterface $responseFactory;

    public function __construct(ServerRequestInterface $request, ResponseFactoryInterface $responseFactory)
    {
        $this->request = $request;
        $this->responseFactory = $responseFactory;
    }

    public function getRequest() : ServerRequestInterface
    {
        return $this->request;
    }

    public function getResponseFactory() : ResponseFactoryInterface
    {
        return $this->responseFactory;
    }
}
