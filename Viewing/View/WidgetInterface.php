<?php
declare(strict_types=1);

namespace ManaPHP\Viewing\View;

interface WidgetInterface
{
    public function run(array $vars = []);
}