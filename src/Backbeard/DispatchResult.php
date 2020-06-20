<?php

namespace Backbeard;

use Psr\Http\Message\ResponseInterface;
use LogicException;

class DispatchResult implements DispatchResultInterface
{
    private bool $dispatched;
    private ?ResponseInterface $response = null;

    public function __construct(bool $dispatched, ResponseInterface $response = null)
    {
        $this->dispatched = $dispatched;
        $this->response = $response;
    }

    public function isDispatched() : bool
    {
        return $this->dispatched;
    }

    public function getResponse() : ResponseInterface
    {
        if ($this->response instanceof ResponseInterface) {
            return $this->response;
        }
        throw new LogicException("Don't call when dispatch return false");    
    }
}
