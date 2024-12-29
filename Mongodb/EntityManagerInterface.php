<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb;

interface EntityManagerInterface extends \ManaPHP\Persistence\EntityManagerInterface
{
    public function normalizeDocument(string $entityClass, array $document): array;
}
