<?php

namespace Backbeard;

class ViewModel implements ViewModelInterface
{
    private $variables;
    private $template;
    
    /**
     * @param array $array
     */
    public function setVariables($array)
    {
        $this->variables = $array;
    }

    public function getVariables()
    {
        return $this->variables;
    }
    
    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }
    
    public function getTemplate()
    {
        return $this->template;   
    }
}
