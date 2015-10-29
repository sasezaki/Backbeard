<?php

namespace Backbeard;

interface DispatchResultInterface
{
    /**
     * @return bool
     */
    public function isDispatched();

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse();
}
