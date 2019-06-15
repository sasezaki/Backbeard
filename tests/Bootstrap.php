<?php

$files = [__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../autoload.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $loader = require $file;

        break;
    }
}

if (! isset($loader)) {
    throw new RuntimeException('vendor/autoload.php could not be found. Did you install via composer?');
}
