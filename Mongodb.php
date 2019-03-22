<?php

namespace ManaPHP;

use ManaPHP\Mongodb\Exception as MongodbException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query as MongodbQuery;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\WriteConcern;

class Mongodb extends Component implements MongodbInterface
{
    /**
     * @var string
     */
    protected $_dsn;

    /**
     * @var string
     */
    protected $_default_db;

    /**
     * @var \MongoDB\Driver\Manager
     */
    protected $_manager;

    /**
     * @var \MongoDB\Driver\WriteConcern
     */
    protected $_writeConcern;

    /**
     * @var int
     */
    protected $_heartbeat = 60;

    /**
     * @var float
     */
    protected $_last_heartbeat;

    /**
     * Mongodb constructor.
     *
     * @param string|array $dsn
     */
    public function __construct($dsn = 'mongodb://127.0.0.1:27017/')
    {
        $this->_dsn = $dsn;

        $path = parse_url($dsn, PHP_URL_PATH);
        $this->_default_db = ($path !== '/' && $path !== null) ? (string)substr($path, 1) : null;
    }

    /**
     * @return string|null
     */
    public function getDefaultDb()
    {
        return $this->_default_db;
    }

    /**
     * @return bool
     */
    protected function _ping()
    {
        try {
            $command = new Command(['ping' => 1]);
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_manager->executeCommand('admin', $command);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @return \MongoDB\Driver\Manager
     */
    protected function _getManager()
    {
        if ($this->_manager === null) {
            $this->logger->debug(['connect to `:dsn`', 'dsn' => $this->_dsn], 'mongodb.connect');

            $this->eventsManager->fireEvent('mongodb:beforeConnect', $this, ['dsn' => $this->_dsn]);
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_manager = new Manager($this->_dsn);
            $this->eventsManager->fireEvent('mongodb:afterConnect', $this);
        }

        if (microtime(true) - $this->_last_heartbeat > $this->_heartbeat && !$this->_ping()) {
            $this->close();
            $this->logger->info(['reconnect to `:dsn`', 'dsn' => $this->_dsn], 'mongodb.reconnect');

            $this->eventsManager->fireEvent('mongodb:beforeConnect', $this, ['dsn' => $this->_dsn]);
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_manager = new Manager($this->_dsn);
            $this->eventsManager->fireEvent('mongodb:afterConnect', $this);
        }

        $this->_last_heartbeat = microtime(true);

        return $this->_manager;
    }

    /**
     * @param string                    $source
     * @param \MongoDb\Driver\BulkWrite $bulk
     *
     * @return \MongoDB\Driver\WriteResult
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkWrite($source, $bulk)
    {
        $namespace = strpos($source, '.') === false ? ($this->_default_db . '.' . $source) : $source;

        if ($this->_writeConcern === null) {
            try {
                $this->_writeConcern = new WriteConcern(WriteConcern::MAJORITY, 10000);
            } catch (\Exception $exception) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $this->eventsManager->fireEvent('mongodb:beforeBulkWrite', $this, compact('namespace', 'bulk'));
        $start_time = microtime(true);
        if ($start_time - $this->_last_heartbeat > 1.0) {
            $this->_last_heartbeat = null;
        }
        try {
            $result = $this->_getManager()->executeBulkWrite($namespace, $bulk, $this->_writeConcern);
        } catch (\Exception $exception) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $elapsed = round(microtime(true) - $start_time, 3);
        $this->eventsManager->fireEvent('mongodb:afterBulkWrite', $this, compact('namespace', 'bulk', 'result', 'elapsed'));
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        if ($bulk->count() !== 1) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            if (!isset($backtrace['function']) && !in_array($backtrace['function'], ['bulkInsert', 'bulkUpdate', 'bulkUpsert'], true)) {
                $this->logger->info(compact('namespace', 'bulk'), 'mongodb.bulk.write');
            }
        }

        return $result;
    }

    /**
     * @param string $source
     * @param array  $document
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function insert($source, $document)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        $bulk = new BulkWrite();

        $bulk->insert($document);

        $this->eventsManager->fireEvent('mongodb:beforeInsert', $this, ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $count = $result->getInsertedCount();

        $this->eventsManager->fireEvent('mongodb:afterInsert', $this, ['namespace' => $namespace]);
        $this->logger->info(compact('count', 'namespace', 'document'), 'mongodb.insert');

        return $count;
    }

    /**
     * @param string  $source
     * @param array[] $documents
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkInsert($source, $documents)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $bulk = new BulkWrite();
        foreach ($documents as $document) {
            $bulk->insert($document);
        }
        $this->eventsManager->fireEvent('mongodb:beforeBulkInsert', $this, ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->eventsManager->fireEvent('mongodb:afterBulkInsert', $this, ['namespace' => $namespace]);
        $count = $result->getInsertedCount();
        $this->logger->info(compact('namespace', 'documents', 'count'), 'mongodb.bulk.insert');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $document
     * @param array  $filter
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function update($source, $document, $filter)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $bulk = new BulkWrite();
        try {
            $bulk->update($filter, key($document)[0] === '$' ? $document : ['$set' => $document], ['multi' => true]);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->eventsManager->fireEvent('mongodb:beforeUpdate', $this, ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->eventsManager->fireEvent('mongodb:afterUpdate', $this);
        $count = $result->getModifiedCount();
        $this->logger->info(compact('namespace', 'document', 'filter', 'count'), 'mongodb.update');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpdate($source, $documents, $primaryKey)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $bulk = new BulkWrite();
        foreach ($documents as $document) {
            $pkValue = $document[$primaryKey];
            unset($document[$primaryKey]);
            try {
                $bulk->update([$primaryKey => $pkValue], key($document)[0] === '$' ? $document : ['$set' => $document]);
            } catch (\Exception $exception) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $this->eventsManager->fireEvent('mongodb:beforeBulkUpdate', $this, ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->eventsManager->fireEvent('mongodb:afterBulkUpdate', $this, ['namespace' => $namespace]);
        $count = $result->getModifiedCount();
        $this->logger->info(compact('namespace', 'documents', 'primaryKey', 'count'), 'mongodb.bulk.update');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function upsert($source, $document, $primaryKey)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $bulk = new BulkWrite();
        try {
            $bulk->update([$primaryKey => $document[$primaryKey]], $document, ['upsert' => true]);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->eventsManager->fireEvent('mongodb:beforeUpsert', $this, ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->eventsManager->fireEvent('mongodb:afterUpsert', $this);
        $count = $result->getUpsertedCount();
        $this->logger->info(compact('count', 'namespace', 'document'), 'mongodb.upsert');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function bulkUpsert($source, $documents, $primaryKey)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $bulk = new BulkWrite();
        foreach ($documents as $document) {
            try {
                $bulk->update([$primaryKey => $document[$primaryKey]], $document, ['upsert' => true]);
            } catch (\Exception $exception) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $this->eventsManager->fireEvent('mongodb:beforeBulkUpsert', $this, ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->eventsManager->fireEvent('mongodb:afterBulkUpsert', $this);
        $count = $result->getUpsertedCount();
        $this->logger->info(compact('count', 'namespace', 'documents'), 'mongodb.bulk.upsert');
        return $count;
    }

    /**
     * @param string $source
     * @param array  $filter
     *
     * @return int
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function delete($source, $filter)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $bulk = new BulkWrite();
        try {
            $bulk->delete($filter);
        } catch (\Exception $exception) {
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->eventsManager->fireEvent('mongodb:beforeDelete', $this, ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->eventsManager->fireEvent('mongodb:afterDelete', $this);
        $count = $result->getDeletedCount();
        $this->logger->info(compact('namespace', 'filter', 'count'), 'mongodb.delete');
        return $count;
    }

    /**
     * @param string         $namespace
     * @param array          $filter
     * @param array          $options
     * @param ReadPreference $readPreference
     *
     * @return array
     */
    protected function _fetchAll($namespace, $filter, $options, $readPreference)
    {
        $cursor = $this->_getManager()->executeQuery($namespace, new MongodbQuery($filter, $options), $readPreference);
        $cursor->setTypeMap(['root' => 'array']);
        return $cursor->toArray();
    }

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $options
     * @param bool|int $secondaryPreferred
     *
     * @return array[]
     */
    public function fetchAll($source, $filter = [], $options = [], $secondaryPreferred = true)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_default_db . '.' . $source);
        if (is_bool($secondaryPreferred)) {
            $readPreference = new ReadPreference($secondaryPreferred ? ReadPreference::RP_SECONDARY_PREFERRED : ReadPreference::RP_PRIMARY);
        } else {
            $readPreference = new ReadPreference($secondaryPreferred);
        }
        $this->eventsManager->fireEvent('mongodb:beforeQuery', $this, compact('namespace', 'filter', 'options'));
        $start_time = microtime(true);

        try {
            $result = $this->_fetchAll($namespace, $filter, $options, $readPreference);
        } catch (\Exception $exception) {
            $result = null;
            $failed = true;
            if (!$this->_ping()) {
                try {
                    $this->close();
                    $result = $this->_fetchAll($namespace, $filter, $options, $readPreference);
                    $failed = false;
                } catch (\Exception $exception) {
                }
            }

            if ($failed) {
                throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        $elapsed = round(microtime(true) - $start_time, 3);
        $this->eventsManager->fireEvent('mongodb:afterQuery', $this, compact('namespace', 'filter', 'options', 'result', 'elapsed'));
        $this->logger->debug(compact('namespace', 'filter', 'options', 'result', 'elapsed'), 'mongodb.query');
        return $result;
    }

    /**
     * @param array  $command
     * @param string $db
     *
     * @return array[]
     */
    public function command($command, $db = null)
    {
        if (!$db) {
            $db = $this->_default_db;
        }

        $this->eventsManager->fireEvent('mongodb:beforeCommand', $this, compact('db', 'command'));
        $start_time = microtime(true);
        if ($start_time - $this->_last_heartbeat > 1.0) {
            $this->_last_heartbeat = null;
        }
        try {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $cursor = $this->_getManager()->executeCommand($db, new Command($command));
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            $result = $cursor->toArray();
        } catch (\Exception $exception) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new MongodbException($exception->getMessage(), $exception->getCode(), $exception);
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */

        $elapsed = round(microtime(true) - $start_time, 3);
        $this->eventsManager->fireEvent('mongodb:afterCommand', $this, compact('db', 'command', 'result', 'elapsed'));
        $count = count($result);
        $command_name = key($command);
        if (strpos('ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
                'authenticate,listDatabases,listCollections,listIndexes', $command_name) !== false) {
            $this->logger->debug(compact('db', 'command', 'count', 'elapsed'), 'mongodb.command.' . $command_name);
        } else {
            $this->logger->info(compact('db', 'command', 'count', 'elapsed'), 'mongodb.command.' . $command_name);
        }

        return $result;
    }

    /**
     * @param string $source
     * @param array  $pipeline
     * @param array  $options
     *
     * @return array
     * @throws \ManaPHP\Mongodb\Exception
     */
    public function aggregate($source, $pipeline, $options = [])
    {
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = $this->_default_db;
            $collection = $source;
        }

        try {
            $command = ['aggregate' => $collection, 'pipeline' => $pipeline];
            if ($options) {
                $command = array_merge($command, $options);
            }
            if (!isset($command['cursor'])) {
                $command['cursor'] = ['batchSize' => 1000];
            }
            return $this->command($command, $db);
        } catch (RuntimeException $e) {
            throw new MongodbException([
                '`:aggregate` aggregate for `:collection` collection failed: :msg',
                'aggregate' => json_encode($pipeline),
                'collection' => $source,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $source
     *
     * @return bool
     */
    public function truncate($source)
    {
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = $this->_default_db;
            $collection = $source;
        }

        try {
            $this->command(['drop' => $collection], $db);
            return true;
        } catch (RuntimeException $e) {
            /**
             * https://github.com/mongodb/mongo/blob/master/src/mongo/base/error_codes.err
             * error_code("NamespaceNotFound", 26)
             */
            if ($e->getCode() === 26) {
                return true;
            } else {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw $e;
            }
        }
    }

    /**
     * @return array
     */
    public function listDatabases()
    {
        $databases = [];
        $result = $this->command(['listDatabases' => 1], 'admin');
        foreach ((array)$result[0]['databases'] as $database) {
            $databases[] = $database['name'];
        }

        return $databases;
    }

    /**
     * @param string $db
     *
     * @return array
     */
    public function listCollections($db = null)
    {
        $collections = [];
        $result = $this->command(['listCollections' => 1], $db);
        foreach ($result as $collection) {
            $collections[] = $collection['name'];
        }

        return $collections;
    }

    /**
     * @param string $collection
     *
     * @return \ManaPHP\Mongodb\Query
     */
    public function query($collection = null)
    {
        return $this->_di->get('ManaPHP\Mongodb\Query', [$this])->from($collection);
    }

    public function close()
    {
        $this->_manager = null;
        $this->_last_heartbeat = null;
    }
}