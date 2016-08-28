<?php

namespace ManaPHP;

/**
 * \ManaPHP\Db\AdapterInterface initializer
 */
interface DbInterface
{

    /**
     * Returns the first row in a SQL query result
     *
     * <code>
     *  $db->fetchOne('SELECT * FROM city');
     *  $db->fetchOne('SELECT * FROM city WHERE city_id =:city_id',['city_id'=>5]);
     * </code>
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     *
     * @return array|false
     */
    public function fetchOne($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC);

    /**
     * Dumps the complete result of a query into an array
     *
     *  <code>
     *  $db->fetchAll('SELECT * FROM city');
     *  $db->fetchAll('SELECT * FROM city WHERE city_id <:city_id',['city_id'=>5]);
     * </code>
     *
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     *
     * @return array
     */
    public function fetchAll($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC);

    /**
     * Inserts data into a table using custom SQL syntax
     *
     * <code>
     *  $db->insert('_student',['age'=>30,'name'=>'Mark']);
     *  $db->insert('_student',[null,30,'Mark']);
     * </code>
     *
     * @param    string $table
     * @param    array  $columnValues
     *
     * @return void
     */
    public function insert($table, $columnValues);

    /**
     * Updates data on a table using custom SQL syntax
     *
     * <code>
     *  $db->update('_student',['name'=>'mark'],'id=2');
     *  $db->update('_student',['name'=>'mark'],['id'=>2]);
     *  $db->update('_student',['name'=>'mark'],'id=:id',['id'=>2]);
     * </code>
     *
     * @param    string       $table
     * @param    array        $columnValues
     * @param    string|array $conditions
     * @param   array         $bind
     *
     * @return    int
     */
    public function update($table, $columnValues, $conditions, $bind = []);

    /**
     * Deletes data from a table using custom SQL syntax
     *
     * <code>
     *  $db->delete('_student','id=1');
     *  $db->delete('_student',['id'=>1]);
     *  $db->delete('_student',['id'=>1]);
     *  $db->delete('_student','id=:id',['id'=>1]);
     * </code>
     *
     * @param  string       $table
     * @param  string|array $conditions
     * @param  array        $bind
     *
     * @return int
     */
    public function delete($table, $conditions, $bind = []);

    /**
     * Appends a LIMIT clause to $sql argument
     *
     * <code>
     *  $db->limit('',10);  //LIMIT 10
     *  $db->limit('',10,100);   //LIMIT 10 OFFSET 100
     * </code>
     *
     * @param    string $sql
     * @param    int    $number
     * @param   int     $offset
     *
     * @return    string
     */
    public function limit($sql, $number, $offset = null);

    /**
     * Active SQL statement in the object
     *
     * @return string
     */
    public function getSQL();

    /**
     * Active SQL statement in the object with replace the bind with value
     *
     * @param int $preservedStrLength
     *
     * @return string
     */
    public function getEmulatedSQL($preservedStrLength = -1);

    /**
     * Active SQL statement in the object
     *
     * @return array
     */
    public function getBind();

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server return rows
     *
     * @param  string $sql
     * @param  array  $bind
     * @param int     $fetchMode
     *
     * @return \PDOStatement
     */
    public function query($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC);

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server don't return any row
     *
     * @param  string $sql
     * @param  array  $bind
     *
     * @return int
     */
    public function execute($sql, $bind = []);

    /**
     * Returns the number of affected rows by the last INSERT/UPDATE/DELETE reported by the database system
     *
     * @return int
     */
    public function affectedRows();

    /**
     * Escapes a column/table/schema name
     *
     * @param string|array $identifier
     *
     * @return string
     */
    public function escapeIdentifier($identifier);

    /**
     * Returns insert id for the auto_increment column inserted in the last SQL statement
     *
     * @return int
     */
    public function lastInsertId();

    /**
     * Starts a transaction in the connection
     *
     * @return void
     */
    public function begin();

    /**
     * Checks whether the connection is under a transaction
     *
     *<code>
     *    $connection->begin();
     *    var_dump($connection->isUnderTransaction()); //true
     *</code>
     *
     * @return bool
     */
    public function isUnderTransaction();

    /**
     * Rollbacks the active transaction in the connection
     *
     * @return void
     */
    public function rollback();

    /**
     * Commits the active transaction in the connection
     *
     * @return void
     */
    public function commit();

    /**
     * @param string
     *
     * @return array
     */
    public function getMetadata($source);

    /**
     * @param string $source
     *
     * @return static
     */
    public function truncateTable($source);
}