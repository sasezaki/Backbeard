<?php

namespace Backbeard\View;

use Backbeard\ViewInterface;
use Backbeard\ViewModelInterface;
use Backbeard\ViewModel;
use Backbeard\RouteMatch;
use SfpStreamView\View as StreamView;
use Psr\Http\Message\ResponseInterface;

class SfpStreamView implements ViewInterface
{
    use TemplatingViewTrait;

    private $streamView;

    public function __construct(StreamView $streamView)
    {
        $this->streamView = $streamView;
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
