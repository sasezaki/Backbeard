<?php

namespace Backbeard;

use Psr\Http\Message\ResponseInterface;
use LogicException;

class DispatchResult implements DispatchResultInterface
{
    private bool $dispatched;
    private ?ResponseInterface $response = null;

    public function __construct($dispatched, $response = null)
    {
        $this->dispatched = $dispatched;
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function isDispatched()
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
