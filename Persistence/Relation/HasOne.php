<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Relation;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\AbstractRelation;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;
use function is_string;

class HasOne extends AbstractRelation
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected string $selfField;
    protected string $thatField;

    public function __construct(string $selfEntity, string|array $that)
    {
        $entityMetadata = Container::get(EntityMetadataInterface::class);

        $this->selfEntity = $selfEntity;
        $this->selfField = $entityMetadata->getPrimaryKey($selfEntity);

        if (is_string($that)) {
            $this->thatEntity = $that;
            $this->thatField = $entityMetadata->getReferencedKey($selfEntity);
        } else {
            list($this->thatEntity, $this->thatField) = $that;
        }
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        $selfField = $this->selfField;
        $thatField = $this->thatField;

        $ids = array_values(array_unique(array_column($r, $selfField)));
        $data = $query->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        foreach ($r as $ri => $rv) {
            $key = $rv[$selfField];
            $r[$ri][$name] = $data[$key] ?? null;
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfField = $this->selfField;
        $thatField = $this->thatField;
        $repository = $this->entityMetadata->getRepository($this->thatEntity);
        return $repository->select()->where([$thatField => $entity->$selfField])->setFetchType(false);
    }
}
