<?php
declare(strict_types=1);

namespace ManaPHP\Viewing\View\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Viewing\ViewInterface;

#[Verbosity(Verbosity::HIGH)]
class ViewRendering
{
    public function __construct(
        public ViewInterface $view,
    ) {

    }
}