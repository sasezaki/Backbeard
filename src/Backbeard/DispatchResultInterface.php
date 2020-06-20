<?php

namespace Backbeard;

use Psr\Http\Message\ResponseInterface;

interface DispatchResultInterface
{
    public function isDispatched() : bool;

    public function getResponse() : ResponseInterface;
}
