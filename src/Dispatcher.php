<?php
namespace Backbeard;
use Generator;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\Http\Segment;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Phly\Mustache\Mustache;

class Dispatcher
{
    private $routing;
    private $serviceLocator;

    public function __construct(Generator $routing, ServiceLocatorInterface $serviceLocator = null)
    {
        $this->routing = $routing;
        $this->serviceLocator = ($serviceLocator) ?: new ServiceManager;
        if (!$this->serviceLocator->has('view')) {
            $this->serviceLocator->setFactory('view', function () {
                $mustache = new Mustache;
                $mustache->setTemplatePath(getcwd());

                return $mustache;
            });
        }
    }

    public function dispatch(Request $request, Response $response = null)
    {
        $this->serviceLocator->setService('request', $request);
        $this->serviceLocator->setService('response', $response = ($response) ?: new HttpResponse());

        while ($this->routing->valid()) {
            $route = $this->routing->key();
            $action = $this->routing->current();

            $router = is_callable($route) ? $route : $this->getRouterByType($route);

            if ($routeResult = $router($request)) {
                $action = $action->bindTo($this->serviceLocator);
                $params = false;
                if ($routeResult instanceof RouteMatch) {
                    $params = $routeResult->getParams();
                } elseif (is_array($routeResult)) {
                    $params = $routeResult;
                }
                $actionResult = ($params) ?
                    call_user_func_array($action, $params) : call_user_func($action, $routeResult);

                if ($actionResult === false) {
                    $this->routing->next();
                    continue;
                } elseif ($actionResult instanceof ActionContinueInterface) {
                    $this->routing->send($actionResult);
                    continue;
                }

                return call_user_func(
                        $this->getActionResultHandler(), $routeResult, $actionResult, $response);
            }
            $this->routing->next();
        }

        return $response;
    }

    public function getRouterByType($route)
    {
        switch (gettype($route)) {
            case 'string':
                return function ($request) use ($route) {
                    $segment = Segment::factory(['route' => $route]);
                    $match = $segment->match($request);
                    if ($match) {
                        $parts = (new \ReflectionClass($segment))->getProperty('parts');
                        $parts->setAccessible(true);
                        $match->setMatchedRouteName(trim($parts->getValue($segment)[0][1], '/'));
                    }

                    return $match;
                };
                break;
            case 'array':
                return function ($request) use ($route) {
                    $routeSegment = $route['route'];
                    unset($route['route']);
                    foreach ($route as $method => $expected) {
                        if ($request->{"get".ucfirst($method)}() !== $expected) {
                            return false;
                        }
                    }
                    $segment = Segment::factory(['route' => $routeSegment]);
                    $match = $segment->match($request);
                    if ($match) {
                        $parts = (new \ReflectionClass($segment))->getProperty('parts');
                        $parts->setAccessible(true);
                        $match->setMatchedRouteName(trim($parts->getValue($segment)[0][1], '/'));
                    }

                    return $match;
                };
                break;
            default:
                throw new \InvalidArgumentException('Invalid router type is passed');
        }
    }

    public function getActionResultHandler()
    {
        return function ($routeResult, $actionResult, $response) {
            if (is_string($actionResult)) {
                $response->setContent($actionResult);

                return $response;
            } elseif (is_array($actionResult)) {
                $view = $this->serviceLocator->get('view');
                if ($routeResult instanceof RouteMatch) {
                    $response->setContent($view->render($routeResult->getMatchedRouteName(), $actionResult));
                }

                return $response;
            } elseif ($actionResult instanceof Response) {
                return $actionResult;
            }
        };
    }
}
