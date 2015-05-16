<?php
namespace Backbeard;

interface ViewInterface
{
    public function assign($array);
    public function render($template, $stream = null);
}
