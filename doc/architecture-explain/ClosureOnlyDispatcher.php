<?php


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Backbeard\DispatcherInterface;
use Backbeard\ActionContinueInterface;
use Backbeard\ClosureActionScope;
use Backbeard\RoutingResult;
use Backbeard\DispatchResult;


class ClosureOnlyDispatcher implements DispatcherInterface
{
    private $routing;
    
    public function __construct(Generator $routing)
    {
        $this->routing = $routing;
    }
    
    public function dispatch(Request $request, Response $response)
    {
        while ($this->routing->valid()) {
            $route = $this->routing->key();
            $action = $this->routing->current();
    
            // dispatch routing
            $routingResult = $this->dispatchRouting($route, $request);
    
            // If Routing Result is Matched, call action
            if ($routingResult->isMatched()) {
    
                // bind Request & Response
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
    
    protected function dispatchRouting(callable $route, Request $request)
    {
        return call_user_func($route, $request);
    }
    
    protected function callAction(RoutingResult $routingResult, callable $action)
    {
        $params = $routingResult->getParams();
        return ($params) ?
            call_user_func_array($action, $params) : call_user_func($action, $routingResult);        
    }
    
    protected function handleActionReturn(RoutingResult $routingResult, $actionReturn, Response $response)
    {
        return $actionReturn;
    }
}
