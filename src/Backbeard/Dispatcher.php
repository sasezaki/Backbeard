<?php

namespace Backbeard;

use Generator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Backbeard\Router\StringRouterInterface;
use Backbeard\Router\ArrayRouterInterface;
use InvalidArgumentException;

class Dispatcher implements DispatcherInterface
{
    /**
     * @var Generator
     */
    private $routing;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * @var StringRouterInterface
     */
    protected $stringRouter;

    /**
     * @var ArrayRouterInterface
     */
    protected $arrayRouter;

    
    public function __construct(Generator $routing, ViewInterface $view, StringRouterInterface $stringRouter, ArrayRouterInterface $arrayRouter = null)
    {
        $this->routing = $routing;
        $this->view = $view;
        $this->stringRouter = $stringRouter;
        $this->arrayRouter = ($arrayRouter) ?: new Router\ArrayRouter($stringRouter);
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return DispatchResultInterface
     */
    public function dispatch(Request $request, Response $response)
    {
        while ($this->routing->valid()) {
            $route = $this->routing->key();
            $action = $this->routing->current();

            // dispatch routing
            $routingResult = $this->dispatchRouting($route, $request);

            // If Routing Result is Matched, call action
            if ($routingResult->isMatched()) {
                
                // inject Request & Response
                if ($action instanceof \Closure) {
                    $actionScope = new ClosureActionScope($request, $response);
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
                
                $response = $this->handleActionReturn($routingResult, $actionReturn, $response);

                return new DispatchResult(true, $response);
            }
            
            $this->routing->next();
        }

        return new DispatchResult(false);
    }
    
    /**
     * @param mixed $route
     * @param Request $request
     * @return RoutingResult
     */
    protected function dispatchRouting($route, Request $request)
    {
        if (is_callable($route)) {
            $routingReturn = call_user_func($route, $request);
        } else {
            $routingReturn = $this->dispatchRoutingByType($route, $request);
        }
        
        if ($routingReturn !== false) {
            if ($routingReturn === true) {
                $routingResult = new RoutingResult(true, array());
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
        } else {
            $routingResult = new RoutingResult(false, []);
        }
        
        return $routingResult;
    }

    /**
     * @param mixed $route
     * @param Request $request
     * @throws InvalidArgumentException
     * @return array|false
     */
    protected function dispatchRoutingByType($route, Request $request)
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
     * @param Request $request
     * @param Response $response
     * @param RoutingResult $routingResult
     * @param mixed $action
     * @throws \UnexpectedValueException
     * @return Response
     */
    protected function callAction(RoutingResult $routingResult, $action)
    {
        if (is_callable($action)) {
            $params = $routingResult->getParams();
            $actionReturn = ($params) ?
                call_user_func_array($action, $params) : call_user_func($action, $routingResult);
        } else {
            throw new \UnexpectedValueException('Unknown Action type');
        }
        
        return $actionReturn;
    }

    /**
     * @param RoutingResult $routingResult
     * @param mixed $actionReturn
     * @param Response $response
     * @return mixed
     */
    protected function handleActionReturn(RoutingResult $routingResult, $actionReturn, Response $response)
    {
        if (is_string($actionReturn)) {
            $response->getBody()->write($actionReturn);
        } elseif (is_array($actionReturn)) {
            $model = $this->view->marshalViewModel($actionReturn, $routingResult);
            $response = $this->view->marshalResponse($model, $response);
        } elseif ($actionReturn instanceof ViewModelInterface) {
            $response = $this->view->marshalResponse($actionReturn, $response);
        } elseif ($actionReturn instanceof Response) {
            $response = $actionReturn;
        }

        return $response;
    }
}
