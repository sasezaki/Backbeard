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

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $actionResponse;

    public function __construct(Generator $routing, ViewInterface $view, RouterInterface $router)
    {
        $this->routing = $routing;
        $this->view = $view;
        $this->router = $router;
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
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

                if (is_int($action)) {
                    $actionResult = $response->withStatus($action);
                } else {
                    $actionScope = new ClosureActionScope($request, $response);
                    $action = $action->bindTo($actionScope);
                    $actionResult = ($params) ?
                        call_user_func_array($action, $params) : call_user_func($action, $routeResult);
                }

                if ($actionResult === false) {
                    $this->routing->next();
                    continue;
                } elseif ($actionResult instanceof ActionContinueInterface) {
                    $this->routing->send($actionResult);
                    continue;
                }

                $this->actionResponse = $this->handleActionResult($routeResult, $actionResult, $response);

                return true;
            }
            $this->routing->next();
        }

        return false;
    }

    /**
     * @throws \LogicException
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getActionResponse()
    {
        if ($this->actionResponse instanceof Response) {
            return $this->actionResponse;
        }

        throw new \LogicException("Don't call when dispatch return false");
    }

    /**
     * @return \Backbeard\TemplatePathResolverInterface
     */
    public function getTemplatePathResolver()
    {
        if (!$this->templatePathResolver) {
            $this->templatePathResolver = new TemplatePathResolver();
        }

        return $this->templatePathResolver;
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function dispatchByType($route, Request $request)
    {
        switch (gettype($route)) {
            case 'string':
                return $this->router->match($route, $request->getUri()->getPath());
                break;
            case 'array':
                $httpMethod = key($route);
                $stringRoute = current($route);

                if (in_array($httpMethod, ['GET', 'POST', 'PUT', 'DELETE'])) {
                    if ($httpMethod !== $request->getMethod()) {
                        return false;
                    }
                }

                $match = $this->router->match($stringRoute, $request->getUri()->getPath());

                if ($match === false) {
                    return false;
                }

                array_shift($route);

                foreach ($route as $method => $rules) {
                    $getter = 'get'.ucfirst($method);
                    foreach ($rules as $param => $callback) {
                        $value = call_user_func([$request, $getter], $param);
                        if (!$callback($value)) {
                            return false;
                        }
                    }
                }

                return true;
                break;
            default:
                throw new InvalidArgumentException('Invalid router type is passed');
        }
    }

    /**
     * @return Response
     */
    protected function handleActionResult(RouteMatch $routeMatch, $actionResult, Response $response)
    {
        if (is_string($actionResult)) {
            $response->getBody()->write($actionResult);

            return $response;
        } elseif (is_array($actionResult)) {
            $template = $this->getTemplatePathResolver()->resolve($routeMatch);

            $model = new ViewModel($actionResult, $template);
            return $this->view->marshalResponse($model, $response);
        } elseif ($actionResult instanceof Response) {
            return $actionResult;
        } elseif (is_int($actionResult)) {
            return $response->withStatus($actionResult);
        }

        return $response;
    }
}
