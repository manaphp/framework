<?php
declare(strict_types=1);

namespace ManaPHP\Viewing\View;

interface AssetInterface
{
    public function get(string $path): string;
}