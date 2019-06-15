<?php

namespace Backbeard\View;

class ViewModel implements ViewModelInterface
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var string
     */
    private $reasonPhrase = '';

    /**
     * @var array
     */
    private $variables;

    /**
     * @var string
     */
    private $template;

    /**
     * @param array $variables
     * @param string $template
     */
    public function __construct(array $variables, string $template, int $code = 200)
    {
        $this->variables = $variables;
        $this->template  = $template;
        $this->code = $code;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getVariables() : array
    {
        return $this->variables;
    }

    public function getTemplate() : string
    {
        return $this->template;
    }
}
