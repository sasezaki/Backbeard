<?php

namespace Backbeard;

interface ViewModelInterface
{
    /**
     * @return array
     */
    public function getVariables();
    
    public function getTemplate();
}
