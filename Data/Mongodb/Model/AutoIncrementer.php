<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb\Model;

use ManaPHP\Component;
use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\Model\ShardingInterface;
use ManaPHP\Data\Mongodb\FactoryInterface;
use ManaPHP\Data\MongodbInterface;
use ManaPHP\Di\Attribute\Inject;

class AutoIncrementer extends Component implements AutoIncrementerInterface
{
    #[Inject] protected ManagerInterface $modelManager;
    #[Inject] protected ShardingInterface $sharding;
    #[Inject] protected FactoryInterface $mongodbFactory;

    protected function createAutoIncrementIndex(MongodbInterface $mongodb, string $source): bool
    {
        $primaryKey = $this->modelManager->getPrimaryKey(static::class);

        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $collection = $mongodb->getPrefix() . $collection;

        $command = [
            'createIndexes' => $collection,
            'indexes'       => [
                [
                    'key'    => [
                        $primaryKey => 1
                    ],
                    'unique' => true,
                    'name'   => $primaryKey
                ]
            ]
        ];

        $mongodb->command($command, $db);

        return true;
    }

    public function getNext(string $model, int $step = 1): int
    {
        list($connection, $source) = $this->sharding->getUniqueShard($model, []);

        $mongodb = $this->mongodbFactory->get($connection);

        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = null;
            $collection = $source;
        }

        $collection = $mongodb->getPrefix() . $collection;

        $command = [
            'findAndModify' => 'auto_increment_id',
            'query'         => ['_id' => $collection],
            'update'        => ['$inc' => ['current_id' => $step]],
            'new'           => true,
            'upsert'        => true
        ];

        $id = $mongodb->command($command, $db)[0]['value']['current_id'];

        if ($id === $step) {
            $this->createAutoIncrementIndex($mongodb, $source);
        }

        return $id;
    }
}