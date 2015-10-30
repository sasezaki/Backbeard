<?php

namespace Backbeard;

use SfpStreamView\View as BaseView;
use Psr\Http\Message\ResponseInterface;

class View extends BaseView implements ViewInterface
{
    public function marshalResponse(ViewModelInterface $model, ResponseInterface $response)
    {
        $template = $model->getTemplate();
        $vars = $model->getVariables();
        
        $this->assign($vars);
        $this->renderResponse($template, $response);
        
        return $response;
    }
}
