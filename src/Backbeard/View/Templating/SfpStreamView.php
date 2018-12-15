<?php

namespace Backbeard\View\Templating;

use Backbeard\View\ViewInterface;
use Backbeard\View\ViewModelInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use SfpStreamView\View as StreamView;

class SfpStreamView implements ViewInterface
{
    use TemplatingViewTrait;

    private $streamView;
    private $responseFactory;

    public function __construct(StreamView $streamView, ResponseFactoryInterface $responseFactory)
    {
        $this->streamView = $streamView;
        $this->responseFactory = $responseFactory;
    }

    public function marshalResponse(ViewModelInterface $model) : ResponseInterface
    {
        $response = $this->responseFactory->createResponse($model->getCode(), $model->getReasonPhrase());

        $template = $model->getTemplate();
        $vars = $model->getVariables();

        $streamView = clone $this->streamView;

        $streamView->assign($vars);
        $streamView->renderResponse($template, $response);

        return $response;
    }
}
