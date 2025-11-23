<?php

declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Immutable extends AbstractConstraint
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    public function validate(Validation $validation): bool
    {
        if ($validation->value === null) {
            return true;
        }

        /** @var Entity $entity */
        $entity = $validation->source;
        $entityClass = $entity::class;
        if (!$entity instanceof Entity) {
            throw new MisuseException('The given class "{entityClass}" is not an entity.', ['entityClass' => $entityClass]);
        }

        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);
        if (!isset($entity->$primaryKey)) {
            return true;
        }

        $repository = $this->entityMetadata->getRepository($entityClass);
        return $validation->value === $repository->value([$primaryKey => $entity->$primaryKey], $validation->field);
    }
}
