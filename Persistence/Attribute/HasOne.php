<?php

declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;
use ManaPHP\Helper\Arr;
use ManaPHP\Persistence\Entity;
use ManaPHP\Query\QueryInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne extends AbstractRelation
{
    protected string $thatField;

    public function __construct(?string $thatField = null)
    {
        $this->thatField = $thatField ?? $this->entityMetadata->getReferencedKey($this->selfEntityClass);
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntityClass);
        $thatField = $this->thatField;

        $ids = Arr::unique_column($r, $selfField);
        $data = $thatQuery->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        foreach ($r as $ri => $rv) {
            $key = $rv[$selfField];
            $r[$ri][$name] = $data[$key] ?? null;
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntityClass);
        $thatField = $this->thatField;
        return $this->getThatQuery()->where([$thatField => $entity->$selfField])->setFetchType(false);
    }
}
