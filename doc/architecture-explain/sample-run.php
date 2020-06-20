<?php

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response;
use Psr\Http\Message\RequestInterface as Request;

use Backbeard\RoutingResult;

require_once dirname(dirname(__DIR__)).'/vendor/autoload.php';
require_once __DIR__.'/ClosureOnlyDispatcher.php';

$request = ServerRequestFactory::fromGlobals();
$response = new Response();

$generator = function () {
    yield function (Request $request) {
        $routingResult = new RoutingResult(true, []);
        return $routingResult;
    } => function () {
        $response = $this->getResponse();
        $response->getBody()->write('Hello');
        return $response;
    };
};

$dispatcher = new ClosureOnlyDispatcher($generator());
echo $dispatcher->dispatch($request, $response)->getResponse()->getBody();