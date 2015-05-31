<?php

namespace Backbeard;

interface ViewModelInterface
{
    /**
     * @param array $array
     */
    public function setVariables($variables);

    public function getVariables();
    
    /**
     * @param string $template
     */
    public function setTemplate($template);
    
    public function getTemplate();
}
