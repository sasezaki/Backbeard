<?php

namespace Backbeard\View;

interface ViewModelInterface
{
    /**
     * @return array
     */
    public function getVariables();
    
    /**
     * @return string
     */
    public function getTemplate();
}
