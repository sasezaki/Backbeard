<?php

declare(strict_types=1);

namespace Backbeard;

class ValidationError implements ActionContinueInterface
{
    /** @var iterable<string> */
    private iterable $messages;

    /**
     * @param iterable<string> $messages
     */
    public function __construct(iterable $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return iterable<string>
     */
    public function getMessages() : iterable
    {
        return $this->messages;
    }
}
