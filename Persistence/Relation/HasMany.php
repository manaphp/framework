<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Relation;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\AbstractRelation;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;
use function is_string;

class HasMany extends AbstractRelation
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected string $selfField;
    protected string $thatField;

    public function __construct(string $selfModel, string|array $that)
    {
        $entityManager = Container::get(EntityMetadataInterface::class);

        $this->selfEntity = $selfModel;
        $this->selfField = $entityManager->getPrimaryKey($selfModel);

        if (is_string($that)) {
            $this->thatEntity = $that;
            $this->thatField = $entityManager->getReferencedKey($selfModel);
        } else {
            list($this->thatEntity, $this->thatField) = $that;
        }
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        $selfField = $this->selfField;
        $thatField = $this->thatField;

        $r_index = [];
        foreach ($r as $ri => $rv) {
            $r_index[$rv[$selfField]] = $ri;
        }

        $ids = array_column($r, $selfField);
        $data = $query->whereIn($thatField, $ids)->fetch();

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
        $selfField = $this->selfField;
        $repository = $this->entityMetadata->getRepository($this->thatEntity);
        return $repository->select()->where([$this->thatField => $entity->$selfField])->setFetchType(true);
    }
}
