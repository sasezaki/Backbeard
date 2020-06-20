<?php

namespace Backbeard\View\Templating;

use Backbeard\RoutingResult;

class TemplatePathResolver implements TemplatePathResolverInterface
{
    private string $suffix = '.phtml';

    public function resolve(RoutingResult $routingResult) : string
    {
        $name = $routingResult->getMatchedRouteName();
        if (strpos(strrev($name), '/') === 0) {
            return $name.'index'.$this->suffix;
        } else {
            return $name.$this->suffix;
        }
    }

    public function setSuffix($suffix) : void 
    {
        $this->suffix = $suffix;
    }
}
