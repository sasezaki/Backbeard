<?php
namespace Backbeard;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

interface DispatcherInterface
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return DispatchResultInterface
     */
    public function dispatch(Request $request, Response $response);
}