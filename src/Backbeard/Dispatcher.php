<?php
declare(strict_types=1);

namespace Backbeard;

use Generator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Backbeard\View\ViewInterface;
use Backbeard\View\ViewModelInterface;
use Backbeard\Router\StringRouterInterface;
use Backbeard\Router\ArrayRouterInterface;

/**
 * Route
 * @psalm-type TCallableRoute = callable(ServerRequestInterface):bool|RoutingResult
 * @psalm-type TRoute = string|array<string, mixed>|TCallableRoute
 *
 * Action
 * @psalm-type TReturnOfAction = bool|string|array<string, mixed>|ViewModelInterface|ResponseInterface|ActionContinueInterface
 * @psalm-type TAction = callable(string ...):TReturnOfAction | callable(RoutingResult):TReturnOfAction
 *
 * @psalm-type TRouting = Generator<TRoute, TAction, ActionContinueInterface, null>
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * @psalm-var TRouting
     */
    private Generator $routing;

    protected ViewInterface $view;

    protected StringRouterInterface $stringRouter;

    protected ArrayRouterInterface $arrayRouter;

    private ResponseFactoryInterface $responseFactory;

    /**
     * @psalm-param TRouting $routing
     */
    public function __construct(Generator $routing, ViewInterface $view, StringRouterInterface $stringRouter, ArrayRouterInterface $arrayRouter, ResponseFactoryInterface $responseFactory)
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

    /**
     * @psalm-param TRoute $route
     */
    protected function dispatchRouting($route, ServerRequestInterface $request) : ?RoutingResult
    {
        if (is_callable($route)) {
            $routingReturn = $this->dispatchRoutingByCallable($route, $request);
            if ($routingReturn === false) {
                return null;
            }
        } else {
            $routingReturn = $this->dispatchRoutingByType($route, $request);
            if ($routingReturn === null) {
                return null;
            }
        }

        return $this->handleRoutingReturn($routingReturn);
    }

    /**
     * @psalm-param TCallableRoute $route
     * @psalm-return bool|RoutingResult
     */
    private function dispatchRoutingByCallable(callable $route, ServerRequestInterface $request)
    {
        return $route($request);
    }

    /**
     * @psalm-param bool|array<string, string>|RoutingResult $routingReturn
     */
    private function handleRoutingReturn($routingReturn) : RoutingResult
    {
        if ($routingReturn === true) {
            $routingResult = new RoutingResult(true, []);
            $routingResult->setMatchedRouteName($request->getUri()->getPath());
            return $routingResult;
        }

        if (is_array($routingReturn)) {
            $params = $routingReturn;
            $routingResult = new RoutingResult(true, $params);
            $routingResult->setMatchedRouteName($request->getUri()->getPath());
            return $routingResult;
        }

        if ($routingReturn instanceof RoutingResult) {
            return $routingReturn;
        }

        throw new \UnexpectedvalueException('Invalid router type is passed');
    }

    /**
     * @param string|array<string, mixed> $route
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

    /**
     * @psalm-param TAction $action
     * @psalm-return TReturnOfAction
     */
    protected function callAction(RoutingResult $routingResult, callable $action)
    {
        $params = $routingResult->getParams();
        $actionReturn = ($params) ? $action(...$params) : $action($routingResult);

        return $actionReturn;
    }

    /**
     * @psalm-param bool|string|array<string, mixed>|ViewModelInterface|ResponseInterface $actionReturn
     */
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
