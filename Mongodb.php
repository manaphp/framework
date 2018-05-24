<?php

namespace ManaPHP;

use ManaPHP\Mongodb\Exception as MongodbException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
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
    protected $_defaultDb;

    /**
     * @var \MongoDB\Driver\Manager
     */
    protected $_manager;

    /**
     * @var \MongoDB\Driver\WriteConcern
     */
    protected $_writeConcern;

    /**
     * Mongodb constructor.
     *
     * @param string|array $dsn
     */
    public function __construct($dsn = 'mongodb://127.0.0.1:27017/')
    {
        $this->_dsn = $dsn;

        $pos = strrpos($dsn, '/');
        if ($pos !== false) {
            $this->_defaultDb = (string)substr($dsn, $pos + 1);
        }
    }

    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return bool
     */
    public function ping()
    {
        for ($i = $this->_manager ? 0 : 1; $i < 2; $i++) {
            try {
                $result = $this->command(['ping' => 1], 'admin')[0];
                if ($result['ok']) {
                    return true;
                }
            } catch (ConnectionTimeoutException $e) {
                $this->_manager = null;
            }
        }

        return false;
    }

    /**
     * @return \MongoDB\Driver\Manager
     */
    protected function _getManager()
    {
        if ($this->_manager === null) {
            $this->fireEvent('mongodb:beforeConnect', ['dsn' => $this->_dsn]);
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_manager = new Manager($this->_dsn);
            $this->fireEvent('mongodb:afterConnect');
        }

        return $this->_manager;
    }

    /**
     * @param string                    $source
     * @param \MongoDb\Driver\BulkWrite $bulk
     *
     * @return \MongoDB\Driver\WriteResult
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function bulkWrite($source, $bulk)
    {
        $namespace = strpos($source, '.') === false ? ($this->_defaultDb . '.' . $source) : $source;

        if ($this->_writeConcern === null) {
            $this->_writeConcern = new WriteConcern(WriteConcern::MAJORITY, 10000);
        }

        $this->fireEvent('mongodb:beforeBulkWrite', ['namespace' => $namespace, 'bulk' => $bulk]);
        $start_time = microtime(true);
        $result = $this->_getManager()->executeBulkWrite($namespace, $bulk, $this->_writeConcern);
        $elapsed = round(microtime(true) - $start_time, 3);
        $this->fireEvent('mongodb:afterBulkWrite', ['namespace' => $namespace, 'bulk' => $bulk, 'result' => $result, 'elapsed' => $elapsed]);

        return $result;
    }

    /**
     * @param string $source
     * @param array  $document
     *
     * @return \MongoDB\BSON\ObjectID|int|string
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function insert($source, $document)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);

        $bulk = new BulkWrite();
        $id = $bulk->insert($document);
        $this->fireEvent('mongodb:beforeInsert', ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->fireEvent('mongodb:afterInsert');

        return $id ?: $document['_id'];
    }

    /**
     * @param string $source
     * @param array  $document
     * @param array  $filter
     * @param array  $updateOptions
     *
     * @return int
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function update($source, $document, $filter, $updateOptions = [])
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);

        $bulk = new BulkWrite();
        $updateOptions += ['multi' => true];

        $bulk->update($filter, ['$set' => $document], $updateOptions);
        $this->fireEvent('mongodb:beforeUpdate', ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->fireEvent('mongodb:afterUpdate');
        return $result->getModifiedCount();
    }

    /**
     * @param string $source
     * @param array  $filter
     * @param array  $deleteOptions
     *
     * @return int|null
     * @throws \MongoDB\Driver\Exception\InvalidArgumentException
     */
    public function delete($source, $filter, $deleteOptions = [])
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);

        $bulk = new BulkWrite();
        $bulk->delete($filter, $deleteOptions);
        $this->fireEvent('mongodb:beforeDelete', ['namespace' => $namespace]);
        $result = $this->bulkWrite($namespace, $bulk);
        $this->fireEvent('mongodb:afterDelete');
        return $result->getDeletedCount();
    }

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $options
     * @param bool|int $secondaryPreferred
     *
     * @return array[]
     */
    public function query($source, $filter = [], $options = [], $secondaryPreferred = true)
    {
        $namespace = strpos($source, '.') !== false ? $source : ($this->_defaultDb . '.' . $source);
        if (is_bool($secondaryPreferred)) {
            $readPreference = $secondaryPreferred ? ReadPreference::RP_SECONDARY_PREFERRED : ReadPreference::RP_PRIMARY;
        } else {
            $readPreference = $secondaryPreferred;
        }
        $this->fireEvent('mongodb:beforeQuery', ['namespace' => $namespace, 'filter' => $filter, 'options' => $options]);
        $start_time = microtime(true);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        $cursor = $this->_getManager()->executeQuery($namespace, new Query($filter, $options), new ReadPreference($readPreference));
        $cursor->setTypeMap(['root' => 'array']);
        $result = $cursor->toArray();
        $elapsed = round(microtime(true) - $start_time, 3);
        $this->fireEvent('mongodb:afterQuery', ['namespace' => $namespace, 'filter' => $filter, 'options' => $options, 'result' => $result, 'elapsed' => $elapsed]);
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
            $db = $this->_defaultDb;
        }

        $this->fireEvent('mongodb:beforeCommand', ['db' => $db, 'command' => $command]);
        $start_time = microtime(true);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $cursor = $this->_getManager()->executeCommand($db, new Command($command));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $result = $cursor->toArray();
        $elapsed = round(microtime(true) - $start_time, 3);
        $this->fireEvent('mongodb:afterCommand', ['db' => $db, 'command' => $command, 'result' => $result, 'elapsed' => $elapsed]);
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
            $db = $this->_defaultDb;
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
    public function truncateTable($source)
    {
        if ($pos = strpos($source, '.')) {
            $db = substr($source, 0, $pos);
            $collection = substr($source, $pos + 1);
        } else {
            $db = $this->_defaultDb;
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
}