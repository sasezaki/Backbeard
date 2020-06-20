<?php

declare(strict_types=1);

namespace Backbeard;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClosureActionScope
{
    private ServerRequestInterface $request;
    private ResponseFactoryInterface $responseFactory;

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
