<?php
declare(strict_types=1);

namespace ManaPHP\Model\Event;

use ManaPHP\Model\ModelInterface;

class ModelDeleted
{
    public function __construct(public ModelInterface $model)
    {

    }
}