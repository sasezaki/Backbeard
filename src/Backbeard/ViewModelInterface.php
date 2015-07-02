<?php

namespace Backbeard;

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
