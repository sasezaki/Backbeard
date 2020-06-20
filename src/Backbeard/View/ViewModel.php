<?php

namespace Backbeard\View;

class ViewModel implements ViewModelInterface
{
    private int $code;

    private string $reasonPhrase = '';

    private array $variables;

    private string $template;

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
