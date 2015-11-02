<?php

namespace Backbeard;

use Psr\Http\Message\ResponseInterface;

interface ViewInterface
{

    public function factoryModel($vars, RoutingResult $routingResult);

    /**
     * @param ViewModelInterface $model
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function marshalResponse(ViewModelInterface $model, ResponseInterface $response);
}
