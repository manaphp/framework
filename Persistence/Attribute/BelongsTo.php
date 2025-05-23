<?php

declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;
use ManaPHP\Helper\Arr;
use ManaPHP\Persistence\Entity;
use ManaPHP\Query\QueryInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo extends AbstractRelation
{
    protected string $selfField;

    public function __construct(?string $selfField = null)
    {
        $this->selfField = $selfField ?? $this->entityMetadata->getReferencedKey($this->thatEntity);
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $selfField = $this->selfField;
        $thatField = $this->entityMetadata->getPrimaryKey($this->thatEntity);

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
        $selfField = $this->selfField;
        $thatField = $this->entityMetadata->getPrimaryKey($this->thatEntity);
        return $this->getThatQuery()->where([$thatField => $entity->$selfField])->setFetchType(false);
    }
}
