<?php

declare(strict_types=1);

namespace Backbeard\View;

interface ViewModelInterface
{
    public function getCode() : int;

    public function getReasonPhrase() : string;

    /** @return array<string, mixed> */
    public function getVariables() : array;

    public function getTemplate() : string;
}
