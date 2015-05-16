<?php
namespace Backbeard;

class TemplatePathResolver implements TemplatePathResolverInterface
{
    private $suffix = '.phtml';
    
    public function resolve(RouteMatch $routeMatch)
    {
        $name = $routeMatch->getMatchedRouteName();
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
