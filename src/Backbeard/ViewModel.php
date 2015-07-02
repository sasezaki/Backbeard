<?php

namespace Backbeard;

class ViewModel implements ViewModelInterface
{
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
