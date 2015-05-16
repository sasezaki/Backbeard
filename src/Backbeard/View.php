<?php
namespace Backbeard;

use Backbeard\ViewInterface;
use SfpStreamView\View as BaseView;
use Psr\Http\Message\StreamInterface;
use Phly\Http\Stream;

class View extends BaseView implements ViewInterface
{
    /**
     * {@inheritdoc}
     */
    public function render($template, $stream = null)
    {
        if ($stream instanceof StreamInterface) {
            $stream = $stream->detach();
        }
        
        parent::render($template, $stream);
        
        return new Stream($stream);
    }    
}
