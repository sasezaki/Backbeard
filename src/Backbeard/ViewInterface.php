<?php

namespace Backbeard;

interface ViewInterface
{
    /**
     * @param array $array
     */
    public function assign($array);

    /**
     * @param string                                    $template
     * @param resoure|\Psr\Http\Message\StreamInterface $stream
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function render($template, $stream = null);
}
