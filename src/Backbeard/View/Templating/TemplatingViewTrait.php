<?php

namespace Backbeard\View\Templating;

use Backbeard\RoutingResult;
use Backbeard\View\ViewModel;
use Backbeard\View\ViewModelInterface;

trait TemplatingViewTrait
{
    private ?TemplatePathResolverInterface $templatePathResolver = null;

    public function getTemplatePathResolver() : TemplatePathResolverInterface
    {
        if (! $this->templatePathResolver) {
            $this->templatePathResolver = new TemplatePathResolver();
        }
        return $this->templatePathResolver;
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function marshalViewModel(RoutingResult $routingResult, array $vars) : ViewModelInterface
    {
        $template = $this->getTemplatePathResolver()->resolve($routingResult);
        return new ViewModel($vars, $template);
    }
}
