<?php

namespace Backbeard;

use Psr\Http\Message\ResponseInterface as Response;
use LogicException;

class DispatchResult implements DispatchResultInterface
{
    private bool $dispatched;
    private $response;

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

    /**
     * @return Response;
     */
    public function getResponse()
    {
        if ($this->response instanceof Response) {
            return $this->response;
        }
        throw new LogicException("Don't call when dispatch return false");    
    }
}
