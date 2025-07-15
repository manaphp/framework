<?php

declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;

abstract class AbstractRelation implements RelationInterface
{
    #[Autowired] protected ?EntityMetadataInterface $entityMetadata = null;

    #[Autowired] protected string $selfEntityClass = '';
    #[Autowired] protected string $thatEntityClass = '';

    public function getThatQuery(): QueryInterface
    {
        return $this->entityMetadata->getRepository($this->thatEntityClass)->select();
    }

    public function getSelfEntityClass(): string
    {
        return $this->selfEntityClass;
    }

    public function getThatEntityClass(): string
    {
        return $this->thatEntityClass;
    }
}
