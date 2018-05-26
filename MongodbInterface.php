<?php
namespace ManaPHP;

interface MongodbInterface
{
    /**
     * Pings a server connection, or tries to reconnect if the connection has gone down
     *
     * @return bool
     */
    public function ping();

    /**
     * @param string $source
     * @param array  $document
     *
     * @return \MongoDB\BSON\ObjectID|int|string
     */
    public function insert($source, $document);

    /**
     * @param string  $source
     * @param array[] $documents
     *
     * @return int
     */
    public function bulkInsert($source, $documents);

    /**
     * @param string $source
     * @param array  $filter
     * @param array  $document
     *
     * @return int
     */
    public function update($source, $filter, $document);

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     */
    public function bulkUpdate($source, $documents, $primaryKey);

    /**
     * @param string $source
     * @param array  $document
     * @param string $primaryKey
     *
     * @return int
     */
    public function upsert($source, $document, $primaryKey);

    /**
     * @param string $source
     * @param array  $documents
     * @param string $primaryKey
     *
     * @return int
     */
    public function bulkUpsert($source, $documents, $primaryKey);

    /**
     * @param string $source
     * @param array  $filter
     *
     * @return int
     */
    public function delete($source, $filter);

    /**
     * @param string   $source
     * @param array    $filter
     * @param array    $options
     * @param bool|int $secondaryPreferred
     *
     * @return array
     */
    public function query($source, $filter = [], $options = [], $secondaryPreferred = true);

    /**
     * @param array  $command
     * @param string $db
     *
     * @return array[]
     */
    public function command($command, $db = null);

    /**
     * @param string $source
     * @param array  $pipeline
     * @param array  $options
     *
     * @return array
     */
    public function aggregate($source, $pipeline, $options = []);

    /**
     * @param string $source
     *
     * @return bool
     */
    public function truncateTable($source);

    /**
     * @return array
     */
    public function listDatabases();

    /**
     * @param string $db
     *
     * @return array
     */
    public function listCollections($db = null);
}