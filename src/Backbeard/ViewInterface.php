<?php

namespace Backbeard;

use Psr\Http\Message\ResponseInterface;

interface ViewInterface
{

    /**
     * @param array $actionReturn
     * @param RoutingResult $routingResult
     * @return ViewModelInterface
     */
    public function marshalViewModel(array $actionReturn, RoutingResult $routingResult);

    /**
     * @param ViewModelInterface $model
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function marshalResponse(ViewModelInterface $model, ResponseInterface $response);
}
