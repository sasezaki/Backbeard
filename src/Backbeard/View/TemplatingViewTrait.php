<?php

namespace Backbeard\View;

use Backbeard\RouteMatch;
use Backbeard\ViewModel;

trait TemplatingViewTrait
{
    /**
     * @var TemplatePathResolverInterface
     */
    private $templatePathResolver;

    public function setTemplatePathResovler(TemplatePathResolverInterface $templatePathResolver)
    {
        $this->templatePathResolver = $templatePathResolver;
    }

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

    public function factoryModel($vars, RouteMatch $routeMatch)
    {
        $template = $this->getTemplatePathResolver()->resolve($routeMatch);
        return new ViewModel($vars, $template);
    }
}
