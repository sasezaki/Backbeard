<?php

namespace Backbeard\View;

use Psr\Http\Message\ResponseInterface;
use Backbeard\RoutingResult;

interface ViewInterface
{

    /**
     * @param RoutingResult $routingResult
     * @param array $actionReturn
     * @return ViewModelInterface
     */
    public function marshalViewModel(RoutingResult $routingResult, array $actionReturn);

    /**
     * @param ViewModelInterface $model
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function marshalResponse(ViewModelInterface $model, ResponseInterface $response);
}
