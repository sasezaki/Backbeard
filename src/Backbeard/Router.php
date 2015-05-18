<?php

namespace Backbeard;

/*
 * This class is borrowed from nikic's FastRoute
 * FastRoute\DataGenerator\RegexBasedAbstract
 */

use FastRoute\RouteParser\Std as RouteParser;

class Router implements RouterInterface
{
    private $routeParser;

    public function __construct(RouteParser $routeParser)
    {
        $this->routeParser = $routeParser;
    }

    public function match($route, $uri)
    {
        $routeData = $this->routeParser->parse($route);
        list($regex, $params) = $this->buildRegexForRoute($routeData);
        if (preg_match("~^$regex$~", $uri, $match) !== 0) {
            array_shift($match);

            return $match;
        }

        return false;
    }

    private function buildRegexForRoute($routeData)
    {
        $regex = '';
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }
            list($varName, $regexPart) = $part;
            if (isset($variables[$varName])) {
                throw new \LogicException(sprintf(
                    'Cannot use the same placeholder "%s" twice', $varName
                ));
            }
            $variables[$varName] = $varName;
            $regex .= '('.$regexPart.')';
        }

        return [$regex, $variables];
    }
}