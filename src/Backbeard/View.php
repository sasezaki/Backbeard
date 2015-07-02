<?php

namespace Backbeard;

use SfpStreamView\View as BaseView;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Stream;

class View extends BaseView implements ViewInterface
{
    public function marshalResponse(ViewModelInterface $model, ResponseInterface $response)
    {
        $template = $model->getTemplate();
        $vars = $model->getVariables();
        $stream = $response->getBody()->detach();
        
        $this->assign($vars);
        $this->render($template, $stream);
        
        return $response->withBody(new Stream($stream));
    }
}
