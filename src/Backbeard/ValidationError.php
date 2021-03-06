<?php

declare(strict_types=1);

namespace Backbeard;

class ValidationError implements ActionContinueInterface
{
    private $messages;

    public function __construct(iterable $messages)
    {
        $this->messages = $messages;
    }

    public function getMessages() : iterable
    {
        return $this->messages;
    }
}
