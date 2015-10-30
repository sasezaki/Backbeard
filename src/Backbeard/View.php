<?php

namespace Backbeard;

use SfpStreamView\View as StreamView;
use Psr\Http\Message\ResponseInterface;

class View implements ViewInterface
{

    /**
     * @var TemplatePathResolverInterface
     */
    private $templatePathResolver;

    public function __construct(StreamView $streamView)
    {
        $this->streamView = $streamView;
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

    public function factoryModel($vars, RouteMatch $routeMatch)
    {
        $template = $this->getTemplatePathResolver()->resolve($routeMatch);
        return new ViewModel($vars, $template);
    }

    public function marshalResponse(ViewModelInterface $model, ResponseInterface $response)
    {
        $template = $model->getTemplate();
        $vars = $model->getVariables();

        $streamView = clone $this->streamView;

        $streamView->assign($vars);
        $streamView->renderResponse($template, $response);
        
        return $response;
    }
}
