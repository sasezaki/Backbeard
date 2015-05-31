<?php

namespace Backbeard;
use Psr\Http\Message\ResponseInterface;

interface ViewInterface
{
    /**
     * @param ViewModelInterface $model
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function marshalResponse(ViewModelInterface $model, ResponseInterface $response);
}
