<?php

declare(strict_types=1);

namespace Backbeard;

use Generator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Backbeard\View\ViewInterface;
use Backbeard\View\ViewModelInterface;
use Backbeard\Router\StringRouterInterface;
use Backbeard\Router\ArrayRouterInterface;

class Dispatcher implements DispatcherInterface
{
    private Generator $routing;

    protected ViewInterface $view;

    protected StringRouterInterface $stringRouter;

    protected ArrayRouterInterface $arrayRouter;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(Generator $routing, ViewInterface $view, StringRouterInterface $stringRouter, ArrayRouterInterface $arrayRouter = null, ResponseFactoryInterface $responseFactory)
    {
        $this->routing = $routing;
        $this->view = $view;
        $this->stringRouter = $stringRouter;
        $this->arrayRouter = $arrayRouter;
        $this->responseFactory = $responseFactory;
    }

    public function dispatch(ServerRequestInterface $request) : ?ResponseInterface
    {
        while ($this->routing->valid()) {
            $route = $this->routing->key();
            $action = $this->routing->current();

            // dispatch routing
            $routingResult = $this->dispatchRouting($route, $request);

            // If Routing Result is Matched, call action
            if ($routingResult instanceof RoutingResult) {
                // bind Request & ResponseFactory
                if ($action instanceof \Closure) {
                    $actionScope = new ClosureActionScope($request, $this->responseFactory);
                    $action = $action->bindTo($actionScope);
                }

                $actionReturn = $this->callAction($routingResult, $action);

                // Should We continue routing ?
                if ($actionReturn === false) {
                    $this->routing->next();
                    continue;
                } elseif ($actionReturn instanceof ActionContinueInterface) {
                    $this->routing->send($actionReturn);
                    continue;
                }

                return $this->handleActionReturn($routingResult, $actionReturn);
            }

            $this->routing->next();
        }

        return null;
    }

    protected function dispatchRouting($route, ServerRequestInterface $request) : ?RoutingResult
    {
        if (is_callable($route)) {
            $routingReturn = $route($request);
            if ($routingReturn === false) {
                return null;
            }
        } else {
            $routingReturn = $this->dispatchRoutingByType($route, $request);
            if ($routingReturn === null) {
                return null;
            }
        }

        if ($routingReturn === true) {
            $routingResult = new RoutingResult(true, []);
            $routingResult->setMatchedRouteName($request->getUri()->getPath());
        } elseif (is_array($routingReturn)) {
            $params = $routingReturn;
            $routingResult = new RoutingResult(true, $params);
            $routingResult->setMatchedRouteName($request->getUri()->getPath());
        } elseif ($routingReturn instanceof RoutingResult) {
            return $routingReturn;
        } else {
            throw new \UnexpectedvalueException('Invalid router type is passed');
        }


        return $routingResult;
    }

    /**
     * @return null|array<string, string>
     */
    protected function dispatchRoutingByType($route, ServerRequestInterface $request) : ?array
    {
        switch (gettype($route)) {
            case 'string':
                return $this->stringRouter->match($route, $request->getUri()->getPath());
            case 'array':
                return $this->arrayRouter->match($route, $request);
            default:
                throw new InvalidArgumentException('Invalid router type is passed');
        }
    }

    protected function callAction(RoutingResult $routingResult, callable $action)
    {
        $params = $routingResult->getParams();
        $actionReturn = ($params) ? $action(...$params) : $action($routingResult);

        return $actionReturn;
    }

    protected function handleActionReturn(RoutingResult $routingResult, $actionReturn) : ResponseInterface
    {
        if (is_string($actionReturn)) {
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($actionReturn);
        } elseif (is_array($actionReturn)) {
            $model = $this->view->marshalViewModel($routingResult, $actionReturn);
            $response = $this->view->marshalResponse($model);
        } elseif ($actionReturn instanceof ViewModelInterface) {
            $response = $this->view->marshalResponse($actionReturn);
        } elseif ($actionReturn instanceof ResponseInterface) {
            $response = $actionReturn;
        } else {
            throw new \RuntimeException('Unexpected Action Return type');
        }

        return $response;
    }
}
