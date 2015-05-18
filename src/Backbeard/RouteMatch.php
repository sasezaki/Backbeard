<?php

namespace Backbeard;

/**
 * RouteInterface match result.
 */
class RouteMatch
{
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
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Set name of matched route.
     *
     * @param string $name
     *
     * @return RouteMatch
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
