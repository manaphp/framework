<?php
declare(strict_types=1);

namespace ManaPHP\Viewing;

interface AssetInterface
{
    public function get(string $path): string;
}