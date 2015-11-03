<?php

namespace Backbeard\View;

use Backbeard\RoutingResult;
use Backbeard\ViewModel;

trait TemplatingViewTrait
{
    /**
     * @var TemplatePathResolverInterface
     */
    private $templatePathResolver;

    /**
     * @return TemplatePathResolverInterface
     */
    public function getTemplatePathResolver()
    {
        if (!$this->templatePathResolver) {
            $this->templatePathResolver = new TemplatePathResolver();
        }
        return $this->templatePathResolver;
    }

    public function marshalViewModel(array $vars, RoutingResult $routingResult)
    {
        $template = $this->getTemplatePathResolver()->resolve($routingResult);
        return new ViewModel($vars, $template);
    }
}
