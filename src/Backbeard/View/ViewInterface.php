<?php

declare(strict_types=1);

namespace Backbeard\View;

use Psr\Http\Message\ResponseInterface;
use Backbeard\RoutingResult;

interface ViewInterface
{
    public function marshalViewModel(RoutingResult $routingResult, array $actionReturn) : ViewModelInterface;

    public function marshalResponse(ViewModelInterface $model) : ResponseInterface;
}
