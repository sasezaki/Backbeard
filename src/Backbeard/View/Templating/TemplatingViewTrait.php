<?php

namespace Backbeard\View\Templating;

use Backbeard\RoutingResult;
use Backbeard\View\ViewModel;
use Backbeard\View\ViewModelInterface;

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
        if (! $this->templatePathResolver) {
            $this->templatePathResolver = new TemplatePathResolver();
        }
        return $this->templatePathResolver;
    }

    public function marshalViewModel(RoutingResult $routingResult, array $vars) : ViewModelInterface
    {
        $template = $this->getTemplatePathResolver()->resolve($routingResult);
        return new ViewModel($vars, $template);
    }
}
