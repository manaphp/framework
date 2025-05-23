<?php

declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Persistence\Entity;
use ManaPHP\Query\QueryInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany extends AbstractRelation
{
    protected string $thatField;
    protected array $orderBy;

    public function __construct(string $thatEntity, ?string $thatField = null, array $orderBy = [])
    {
        $this->thatEntity = $thatEntity;
        $this->thatField = $thatField ?? $this->entityMetadata->getReferencedKey($this->selfEntity);
        $this->orderBy = $orderBy;
    }

    public function getThatQuery(): QueryInterface
    {
        return parent::getThatQuery()->orderBy($this->orderBy);
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntity);
        $thatField = $this->thatField;

        $r_index = [];
        foreach ($r as $ri => $rv) {
            $r_index[$rv[$selfField]] = $ri;
        }

        $ids = array_column($r, $selfField);
        $data = $thatQuery->whereIn($thatField, $ids)->fetch();

        if (isset($data[0]) && !isset($data[0][$thatField])) {
            throw new MisuseException(['missing `{1}` field in `{2}` with', $thatField, $name]);
        }

        $rd = [];
        foreach ($data as $dv) {
            $rd[$r_index[$dv[$thatField]]][] = $dv;
        }

        foreach ($r as $ri => $rv) {
            $r[$ri][$name] = $rd[$ri] ?? [];
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntity);
        return $this->getThatQuery()->where([$this->thatField => $entity->$selfField])->setFetchType(true);
    }
}
