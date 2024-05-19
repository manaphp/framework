<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Query\QueryInterface;

interface RelationInterface
{
    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array;

    public function lazyLoad(Entity $entity): QueryInterface;
}