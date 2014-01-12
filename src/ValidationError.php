<?php
namespace Backbeard;

class ValidationError implements ActionContinueInterface
{
    private $messages;

    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
