<?php

declare(strict_types=1);

namespace ManaPHP\Exception;

class ExtensionNotInstalledException extends RuntimeException
{
    public function __construct(string $extension)
    {
        parent::__construct("'$extension' is not installed, or the extension is not loaded");
    }
}
