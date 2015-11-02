<?php

namespace Backbeard\View;

use Backbeard\RoutingResult;

class TemplatePathResolver implements TemplatePathResolverInterface
{
    private $suffix = '.phtml';

    /**
     * @return string
     */
    public function resolve(RoutingResult $routingResult)
    {
        $name = $routingResult->getMatchedRouteName();
        if (strpos(strrev($name), '/') === 0) {
            return $name.'index'.$this->suffix;
        } else {
            return $name.$this->suffix;
        }
    }

    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
    }
}
