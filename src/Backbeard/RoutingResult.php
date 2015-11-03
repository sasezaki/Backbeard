<?php

namespace Backbeard;

/**
 * Routing Result.
 */
class RoutingResult
{
    /**
     * @var bool
     */
    protected $matched;
    
    /**
     * Match parameters.
     *
     * @var array
     */
    protected $params = array();

    /**
     * Matched route name.
     *
     * @var string
     */
    protected $matchedRouteName;
    
    /**
     * Create a RouteMatch with given parameters.
     *
     * @params bool $matched
     * @param array $params
     */
    public function __construct($matched, array $params = [])
    {
        $this->matched = $matched;
        $this->params = $params;
    }
    
    public function isMatched()
    {
        return $this->matched;
    }

    /**
     * Set name of matched route.
     *
     * @param string $name
     */
    public function setMatchedRouteName($name)
    {
        $this->matchedRouteName = $name;
    }

    /**
     * Get name of matched route.
     *
     * @return string
     */
    public function getMatchedRouteName()
    {
        return $this->matchedRouteName;
    }

    /**
     * Get all parameters.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
