<?php

namespace Backbeard;

class ViewModel implements ViewModelInterface
{
    private $variables;
    private $template;
    
    public function __construct($variables, $template)
    {
        $this->variables = $variables;
        $this->template  = $template;
    }

    public function getVariables()
    {
        return $this->variables;
    }
    
    public function getTemplate()
    {
        return $this->template;   
    }
}
