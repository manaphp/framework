<?php

namespace ManaPHP\Data\Db;

interface ConnectionInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @param string $sql
     * @param array  $bind
     * @param bool   $has_insert_id
     *
     * @return int
     * @throws \ManaPHP\Data\Db\ConnectionException
     * @throws \ManaPHP\Exception\InvalidValueException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function execute($sql, $bind = [], $has_insert_id = false);

    /**
     * @param string $sql
     * @param array  $bind
     * @param int    $mode
     *
     * @return array
     */
    public function query($sql, $bind, $mode);

    /**
     * @param string $source
     *
     * @return array
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getMetadata($source);

    /**
     * @return bool
     */
    public function begin();

    /**
     * @return bool
     */
    public function commit();

    /**
     * @return bool
     */
    public function rollback();

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function truncate($source);

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function drop($source);

    /**
     * @param string $schema
     *
     * @return array
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function getTables($schema = null);

    /**
     * @param string $source
     *
     * @return bool
     * @throws \ManaPHP\Data\Db\Exception
     */
    public function tableExists($source);

    /**
     * @param array $params
     *
     * @return string
     */
    public function buildSql($params);

    /**
     * @param string $sql
     *
     * @return string
     */
    public function replaceQuoteCharacters($sql);
}