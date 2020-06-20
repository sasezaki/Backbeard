<?php

declare(strict_types=1);

namespace Backbeard;

/**
 * Routing Result.
 */
class RoutingResult
{
    protected bool $matched;

    protected array $params = [];

    /**
     * Matched route name.
     */
    protected ?string $matchedRouteName = null;

    /**
     * Create a RouteMatch with given parameters.
     *
     * @param array<string, mixed> $params
     */
    public function __construct(bool $matched, array $params = [])
    {
        $this->matched = $matched;
        $this->params = $params;
    }

    public function isMatched() : bool
    {
        return $this->matched;
    }

    /**
     * Set name of matched route.
     */
    public function setMatchedRouteName(string $name) : void
    {
        $this->matchedRouteName = $name;
    }

    /**
     * Get name of matched route.
     */
    public function getMatchedRouteName() : ?string
    {
        return $this->matchedRouteName;
    }

    /**
     * Get all parameters.
     *
     * @return array<string, mixed>
     */
    public function getParams() : array
    {
        return $this->params;
    }
}
