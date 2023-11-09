<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Trim extends AbstractRule
{
    public function __construct(public string $characters = " \n\r\t\v\x00", public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        $validation->value = \trim($validation->value, $this->characters);

        return true;
    }
}