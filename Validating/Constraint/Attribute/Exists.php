<?php

declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function class_exists;
use function get_class;
use function strrpos;
use function substr;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Exists extends AbstractConstraint
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    public function validate(Validation $validation): bool
    {
        if (!$validation->source instanceof Entity) {
            throw new MisuseException('The given class "{class}" is not an entity.', ['class' => get_class($validation->source)]);
        }

        $value = $validation->value;
        $field = $validation->field;
        if (!$value) {
            return true;
        }

        if (preg_match('#^(.*)_id$#', $validation->field, $match)) {
            $entityName = $validation->source::class;
            $className = substr($entityName, 0, strrpos($entityName, '\\') + 1) . Str::pascalize($match[1]);
            if (!class_exists($className)) {
                $className = 'App\\Entities\\' . Str::pascalize($match[1]);
            }
        } else {
            throw new InvalidValueException('The field "{field}" name must end with "_id" to infer the entity class.', ['field' => $field]);
        }

        if (!class_exists($className)) {
            throw new InvalidValueException('The entity class "{className}" for field "{field}" does not exist.', ['field' => $field, 'className' => $className]);
        }

        try {
            $repository = $this->entityMetadata->getRepository($className);
            $repository->get($value);
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (Exception) {
            return false;
        }

        return true;
    }
}
