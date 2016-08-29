<?php

namespace ManaPHP\Mvc;

/**
 * ManaPHP\Mvc\ModelInterface initializer
 */
interface ModelInterface
{
    /**
     * Returns table name mapped in the model
     * <code>
     *  $city->getSource();
     * </code>
     *
     * @return string
     */
    public function getSource();

    /**
     * Sets both read/write connection services
     *
     * @param string $connectionService
     *
     * @return static
     */
    public function setConnectionService($connectionService);

    /**
     * Sets the DependencyInjection connection service used to write data
     *
     * @param string $connectionService
     *
     * @return static
     */
    public function setWriteConnectionService($connectionService);

    /**
     * Sets the DependencyInjection connection service used to read data
     *
     * @param string $connectionService
     *
     * @return static
     */
    public function setReadConnectionService($connectionService);

    /**
     * Returns DependencyInjection connection service used to read data
     *
     * @return string
     */
    public function getReadConnectionService();

    /**
     * Returns DependencyInjection connection service used to write data
     *
     * @return string
     */
    public function getWriteConnectionService();

    /**
     * Gets internal database connection
     *
     * @return \ManaPHP\DbInterface
     */
    public function getReadConnection();

    /**
     * Gets internal database connection
     *
     * @return \ManaPHP\DbInterface
     */
    public function getWriteConnection();

    /**
     * Assigns values to a model from an array
     * <code>
     *  $city->assign(['city_id'=>1,'city_name'=>'beijing']);
     *  $city->assign(['city_id'=>1,'city_name'=>'beijing'],['city_name']);
     * </code>
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     */
    public function assign($data, $whiteList = null);

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * <code>
     *  $cities=City::find(['country_id'=>2]);
     *  $cities=City::find(['conditions'=>['country_id'=>2],'order'=>'city_id desc']);
     *  $cities=City::find([['country_id'=>2],'order'=>'city_id desc']);
     *  $cities=City::find(['conditions'=>'country_id =:country_id','bind'=>['country_id'=>2]]);
     *
     * </code>
     * @param    string|array $parameters
     * @param   int|array     $cacheOptions
     *
     * @return  static[]
     */
    public static function find($parameters = null, $cacheOptions = null);

    /**
     * alias of find
     *
     * @param    string|array $parameters
     * @param   int|array     $cacheOptions
     *
     * @return  static[]
     */
    public static function findAll($parameters = null, $cacheOptions = null);

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *  $city=City::findFirst(10);
     *  $city=City::findFirst(['city_id'=>10]);
     *  $city=City::findFirst(['conditions'=>['city_id'=>10]]);
     *  $city=City::findFirst(['conditions'=>'city_id =:city_id','bind'=>['city_id'=>10]]);
     * </code>
     *
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return static|false
     */
    public static function findFirst($parameters = null, $cacheOptions = null);

    /**
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return bool
     */
    public static function exists($parameters = null, $cacheOptions = null);

    /**
     * Create a criteria for a special model
     *
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return \ManaPHP\Mvc\Model\QueryBuilderInterface
     */
    public static function query($dependencyInjector = null);

    /**
     * Allows to count how many records match the specified conditions
     *
     * <code>
     * City::count(['country_id'=>2]);
     * </code>
     *
     * @param string|array $parameters
     * @param string       $column
     * @param int|array    $cacheOptions
     *
     * @return int|array
     */
    public static function count($parameters = null, $column = '*', $cacheOptions = null);

    /**
     * Allows to calculate a summary on a column that match the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     */
    public static function sum($column, $parameters = null, $cacheOptions = null);

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     */
    public static function max($column, $parameters = null, $cacheOptions = null);

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     */
    public static function min($column, $parameters = null, $cacheOptions = null);

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return double
     */
    public static function average($column, $parameters = null, $cacheOptions = null);

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * @param  array $data
     * @param  array $whiteList
     *
     * @return void
     */
    public function save($data = null, $whiteList = null);

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @param  array $data
     * @param  array $whiteList
     *
     * @return void
     */
    public function create($data = null, $whiteList = null);

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     * @param  array $data
     * @param  array $whiteList
     *
     * @return void
     */
    public function update($data = null, $whiteList = null);

    /**
     * @param array        $columnValues
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     */
    public static function updateAll($columnValues, $conditions, $bind = []);

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * @return void
     */
    public function delete();

    /**
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     */
    public static function deleteAll($conditions, $bind = []);

    /**
     * Returns the instance as an array representation
     *
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @return array
     */
    public function toArray();

    /**
     * Returns the internal snapshot data
     *
     * @return array
     */
    public function getSnapshotData();

    /**
     * Returns a list of changed values
     *
     * @return array
     */
    public function getChangedFields();

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param string|array $fields
     *
     * @return bool
     */
    public function hasChanged($fields);
}