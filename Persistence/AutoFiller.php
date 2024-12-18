<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Identifying\IdentityInterface;
use ReflectionNamedType;
use ReflectionProperty;

class AutoFiller implements AutoFillerInterface
{
    #[Autowired] protected IdentityInterface $identity;

    #[Autowired] protected array $created_time = ['created_time', 'created_at'];
    #[Autowired] protected array $creator_id = ['creator_id'];
    #[Autowired] protected array $creator_name = ['creator_name'];

    #[Autowired] protected array $updated_time = ['updated_time', 'updated_at'];
    #[Autowired] protected array $updator_id = ['updator_id'];
    #[Autowired] protected array $updator_name = ['updator_name'];

    #[Autowired] protected string $date_format = 'Y-m-d H:i:s';

    protected function findField(Entity $entity, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (property_exists($entity, $field)) {
                return $field;
            }
        }

        return null;
    }

    protected function findCreatedTime(Entity $entity): ?string
    {
        return $this->findField($entity, $this->created_time);
    }

    protected function findCreatorId(Entity $entity): ?string
    {
        return $this->findField($entity, $this->creator_id);
    }

    protected function findCreatorName(Entity $entity): ?string
    {
        return $this->findField($entity, $this->creator_name);
    }

    protected function findUpdatedTime(Entity $entity): ?string
    {
        return $this->findField($entity, $this->updated_time);
    }

    protected function findUpdatorId(Entity $entity): ?string
    {
        return $this->findField($entity, $this->updator_id);
    }

    protected function findUpdatorName(Entity $entity): ?string
    {
        return $this->findField($entity, $this->updator_name);
    }

    public function setTime(Entity $entity, string $field, int $timestamp): void
    {
        $rProperty = new ReflectionProperty($entity, $field);
        if (($rType = $rProperty->getType()) && $rType instanceof ReflectionNamedType) {
            $type = $rType->getName();
            $entity->$field = $type === 'int' ? $timestamp : date($this->date_format);
        }
    }

    public function fillCreated(Entity $entity): void
    {
        $timestamp = time();
        $user_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $user_name = $this->identity->isGuest() ? '' : $this->identity->getName();

        $created_time = $this->findCreatedTime($entity);
        if ($created_time !== null && !isset($entity->$created_time)) {
            $this->setTime($entity, $created_time, $timestamp);
        }

        $creator_id = $this->findCreatorId($entity);
        if ($creator_id !== null && !isset($entity->$creator_id)) {
            $entity->$creator_id = $user_id;
        }

        $creator_name = $this->findCreatorName($entity);
        if ($creator_name !== null && !isset($entity->$creator_name)) {
            $entity->$creator_name = $user_name;
        }

        $updated_time = $this->findUpdatedTime($entity);
        if ($updated_time !== null && !isset($entity->$updated_time)) {
            $this->setTime($entity, $updated_time, $timestamp);
        }

        $updator_id = $this->findUpdatorId($entity);
        if ($updator_id !== null && !isset($entity->$updator_id)) {
            $entity->$updator_id = $user_id;
        }

        $updator_name = $this->findUpdatorName($entity);
        if ($updator_name !== null && !isset($entity->$updator_name)) {
            $entity->$updator_name = $user_name;
        }
    }

    public function fillUpdated(Entity $entity): void
    {
        $timestamp = time();
        $user_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $user_name = $this->identity->isGuest() ? '' : $this->identity->getName();

        $updated_time = $this->findUpdatedTime($entity);
        if ($updated_time !== null) {
            $this->setTime($entity, $updated_time, $timestamp);
        }

        $updator_id = $this->findUpdatorId($entity);
        if ($updator_id !== null) {
            $entity->$updator_id = $user_id;
        }

        $updator_name = $this->findUpdatorName($entity);
        if ($updator_name !== null) {
            $entity->$updator_name = $user_name;
        }
    }
}