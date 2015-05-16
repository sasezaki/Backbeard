<?php
namespace Backbeard;

use Generator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use InvalidArgumentException;

class Dispatcher
{
    /**
     * @var Generator
     */
    private $routing;
    
    /**
     * @var ViewInterface
     */
    private $view;
    
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TemplatePathResolverInterface
     */
    private $templatePathResolver;
    
    public function __construct(Generator $routing, ViewInterface $view, RouterInterface $router)
    {
        $this->routing = $routing;
        $this->view = $view;
        $this->router = $router;
    }

    public function dispatch(Request $request, Response $response)
    {
        while ($this->routing->valid()) {
            $route = $this->routing->key();
            $action = $this->routing->current();

            if (is_callable($route)) {
                $routeResult = call_user_func($route, $request);
            } else {
                $routeResult = $this->dispatchByType($route, $request);
            }
            
            if ($routeResult !== false) {
                $params = false;            
                if ($routeResult instanceof RouteMatch) {
                    $params = $routeResult->getParams();
                } elseif (is_array($routeResult)) {
                    $params = $routeResult;
                    $routeResult = new RouteMatch($params);
                    $routeResult->setMatchedRouteName($request->getUri()->getPath());
                } else {
                    $routeResult = new RouteMatch(array());
                    $routeResult->setMatchedRouteName($request->getUri()->getPath());
                }            
                
                $action = $action->bindTo($response);
                
                $actionResult = ($params) ?
                    call_user_func_array($action, $params) : call_user_func($action, $routeResult);
                
                if ($actionResult === false) {
                    $this->routing->next();
                    continue;
                } elseif ($actionResult instanceof ActionContinueInterface) {
                    $this->routing->send($actionResult);
                    continue;
                }

                return $this->handleActionResult($routeResult, $actionResult, $response);
            }
            $this->routing->next();
        }
        
        return $response;
    }
    
    /**
     * @return \SfpBackbeard\TemplatePathResolverInterface
     */
    public function getTemplatePathResolver()
    {
        if (!$this->templatePathResolver) {
            $this->templatePathResolver = new TemplatePathResolver();
        }
        
        return $this->templatePathResolver;
    }

    protected function dispatchByType($route, $request)
    {
        switch (gettype($route)) {
            case 'string':
                return $this->router->match($route, (string)$request->getUri());
                break;
            case 'array':
                // todo implements
                break;
            default:
                throw new InvalidArgumentException('Invalid router type is passed');
        }
    }

    protected function handleActionResult(RouteMatch $routeMatch, $actionResult, Response $response)
    {
        if (is_string($actionResult)) {
            $response->getBody()->write($actionResult);
            return $response;
        } elseif (is_array($actionResult)) {
            $template = $this->getTemplatePathResolver()->resolve($routeMatch);

            $view = $this->view;
            $view->assign($actionResult);

            $body = $view->render($template, $response->getBody());
            
            return $response->withBody($body);
        } elseif ($actionResult instanceof Response) {
            return $actionResult;
        } elseif (is_int($actionResult)) {
            return $response->withStatus($actionResult);
        }
        
        return $response;
    }
}
