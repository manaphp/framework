<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Di;
use ManaPHP\Di\FactoryDefault;
use ManaPHP\Mvc\Model\Exception;

/**
 * ManaPHP\Mvc\Model
 *
 * <p>ManaPHP\Mvc\Model connects business objects and database tables to create
 * a persistent domain model where logic and data are presented in one wrapping.
 * It's an implementation of the object-relational mapping (ORM).</p>
 *
 * <p>A model represents the information (data) of the application and the rules to manipulate that data.
 * Models are primarily used for managing the rules of interaction with a corresponding database table.
 * In most cases, each table in your database will correspond to one model in your application.
 * The bulk of your application’s business logic will be concentrated in the models.</p>
 *
 * <code>
 *
 * $robot = new Robots();
 * $robot->type = 'mechanical';
 * $robot->name = 'Boy';
 * $robot->year = 1952;
 * if ($robot->save() == false) {
 *  echo "Umh, We can store robots: ";
 *  foreach ($robot->getMessages() as $message) {
 *    echo $message;
 *  }
 * } else {
 *  echo "Great, a new robot was saved successfully!";
 * }
 * </code>
 *
 * @method initialize()
 * @method onConstruct()
 *
 * method beforeCreate()
 * method afterCreate()
 *
 * method beforeSave()
 * method afterSave()
 *
 * method afterFetch()
 *
 * method beforeUpdate()
 * method afterUpdate()
 *
 * method beforeDelete()
 * method afterDelete()
 *
 * @property \ManaPHP\Mvc\Model\MetaDataInterface $modelsMetadata
 * @property \ManaPHP\Mvc\Model\ManagerInterface  $modelsManager
 */
class Model extends Component implements ModelInterface
{
    /**
     * @var array
     */
    protected $_snapshot = [];

    /**
     * @var array
     */
    protected static $_initialized = [];

    /**
     * \ManaPHP\Mvc\Model constructor
     *
     * @param array                $data
     * @param \ManaPHP\DiInterface $dependencyInjector
     */
    final public function __construct($data = [], $dependencyInjector = null)
    {
        $this->_dependencyInjector = $dependencyInjector ?: FactoryDefault::getDefault();

        $modelName = get_class($this);

        if (!isset(self::$_initialized[$modelName])) {
            if (method_exists($this, 'initialize')) {
                $this->initialize();
            }

            self::$_initialized[$modelName] = true;
        }

        /**
         * This allows the developer to execute initialization stuff every time an instance is created
         */
        if (method_exists($this, 'onConstruct')) {
            $this->onConstruct();
        }

        if (count($data) !== 0) {
            $this->_snapshot = $data;
            foreach ($data as $attribute => $value) {
                $this->{$attribute} = $value;
            }

            if (method_exists($this, 'afterFetch')) {
                $this->afterFetch();
            }
        }
    }

    /**
     * Sets table name which model should be mapped
     *
     * @param $source
     *
     * @return static
     */
    public function setSource($source)
    {
        $this->modelsManager->setModelSource($this, $source);

        return $this;
    }

    /**
     * Returns table name mapped in the model
     *
     * @return string
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function getSource()
    {
        return $this->modelsManager->getModelSource($this);
    }

    /**
     * Sets the DependencyInjection connection service name
     *
     * @param string $connectionService
     *
     * @return static
     */
    public function setConnectionService($connectionService)
    {
        $this->modelsManager->setConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Sets the DependencyInjection connection service name used to read data
     *
     * @param string $connectionService
     *
     * @return static
     */
    public function setReadConnectionService($connectionService)
    {
        $this->modelsManager->setReadConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Sets the DependencyInjection connection service name used to write data
     *
     * @param string $connectionService
     *
     * @return static
     */
    public function setWriteConnectionService($connectionService)
    {
        $this->modelsManager->setWriteConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Returns the DependencyInjection connection service name used to read data related the model
     *
     * @return string
     */
    public function getReadConnectionService()
    {
        return $this->modelsManager->getReadConnectionService($this);
    }

    /**
     * Returns the DependencyInjection connection service name used to write data related to the model
     *
     * @return string
     */
    public function getWriteConnectionService()
    {
        return $this->modelsManager->getWriteConnectionService($this);
    }

    /**
     * Gets the connection used to read data for the model
     *
     * @return \ManaPHP\DbInterface
     */
    public function getReadConnection()
    {
        return $this->modelsManager->getReadConnection($this);
    }

    /**
     * Gets the connection used to write data to the model
     *
     * @return \ManaPHP\DbInterface
     */
    public function getWriteConnection()
    {
        return $this->modelsManager->getWriteConnection($this);
    }

    /**
     * Assigns values to a model from an array
     *
     *<code>
     *$robot->assign(array(
     *  'type' => 'mechanical',
     *  'name' => 'Boy',
     *  'year' => 1952
     *));
     *</code>
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return static
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function assign($data, $whiteList = null)
    {
        foreach ($this->modelsMetadata->getAttributes($this) as $attribute) {
            if (!isset($data[$attribute])) {
                continue;
            }

            if ($whiteList !== null && !in_array($attribute, $whiteList, true)) {
                continue;
            }

            $this->{$attribute} = $data[$attribute];
        }

        return $this;
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * <code>
     *
     * //How many robots are there?
     * $robots = Robots::find();
     * echo "There are ", count($robots), "\n";
     *
     * //How many mechanical robots are there?
     * $robots = Robots::find("type='mechanical'");
     * echo "There are ", count($robots), "\n";
     *
     * //Get and print virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name"));
     * foreach ($robots as $robot) {
     *       echo $robot->name, "\n";
     * }
     *
     * //Get first 100 virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name", "limit" => 100));
     * foreach ($robots as $robot) {
     *       echo $robot->name, "\n";
     * }
     * </code>
     *
     * @param    string|array $parameters
     * @param  int|array      $cacheOptions
     *
     * @return  static[]
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function find($parameters = null, $cacheOptions = null)
    {
        $dependencyInjector = Di::getDefault();

        /**
         * @var $modelsManager \ManaPHP\Mvc\Model\Manager
         */
        $modelsManager = $dependencyInjector->getShared('modelsManager');

        $modelName = get_called_class();

        $builder = $modelsManager->createBuilder($parameters)
            ->columns($dependencyInjector->modelsMetadata->getColumnProperties($modelName))
            ->from($modelName);

        $resultset = $builder->execute($cacheOptions);

        $modelInstances = [];
        foreach ($resultset as $result) {
            $modelInstances[] = new static($result, $dependencyInjector);
        }

        return $modelInstances;
    }

    /**
     * alias of find
     *
     * @param    string|array $parameters
     * @param   int|array     $cacheOptions
     *
     * @return  static[]
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    final public static function findAll($parameters = null, $cacheOptions = null)
    {
        return self::find($parameters, $cacheOptions);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *
     * //What's the first robot in robots table?
     * $robot = Robots::findFirst();
     * echo "The robot name is ", $robot->name;
     *
     * //What's the first mechanical robot in robots table?
     * $robot = Robots::findFirst("type='mechanical'");
     * echo "The first mechanical robot name is ", $robot->name;
     *
     * //Get first virtual robot ordered by name
     * $robot = Robots::findFirst(array("type='virtual'", "order" => "name"));
     * echo "The first virtual robot name is ", $robot->name;
     *
     * </code>
     *
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return static|false
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function findFirst($parameters = null, $cacheOptions = null)
    {
        $dependencyInjector = Di::getDefault();
        $modelsManager = $dependencyInjector->getShared('modelsManager');

        if (is_numeric($parameters)) {
            $modelsMetadata = $dependencyInjector->getShared('modelsMetadata');
            $primaryKeys = $modelsMetadata->getPrimaryKeyAttributes(get_called_class());

            if (count($primaryKeys) !== 1) {
                throw new Exception('parameter is integer, but the model\'s primary key has more than one column');
            }

            $parameters = [$primaryKeys[0] => $parameters];
        } elseif (is_string($parameters)) {
            $parameters = [$parameters];
        }

        $modelName = get_called_class();

        /**
         * @var $modelsManager \ManaPHP\Mvc\Model\Manager
         */
        $builder = $modelsManager->createBuilder($parameters)
            ->columns($dependencyInjector->modelsMetadata->getColumnProperties($modelName))
            ->from($modelName)
            ->limit(1);

        $resultset = $builder->execute($cacheOptions);

        if (is_array($resultset) && isset($resultset[0])) {
            return new static($resultset[0], $dependencyInjector);
        } else {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return false;
        }
    }

    /**
     * Create a criteria for a specific model
     *
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return \ManaPHP\Mvc\Model\QueryBuilderInterface
     */
    public static function query($dependencyInjector = null)
    {
        $dependencyInjector = $dependencyInjector ?: Di::getDefault();

        return $dependencyInjector->getShared('modelsManager')->createBuilder()->addFrom(get_called_class());
    }

    /**
     * Checks if the current record already exists or not
     *
     * @return boolean
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected function _exists()
    {
        $primaryKeys = $this->modelsMetadata->getPrimaryKeyAttributes($this);
        if (count($primaryKeys) === 0) {
            return false;
        }

        $conditions = [];
        $bind = [];

        foreach ($primaryKeys as $attributeField) {
            if (!isset($this->{$attributeField})) {
                return false;
            }

            $bindKey = $attributeField;

            $conditions[] = $attributeField . ' =:' . $bindKey;
            $bind[$bindKey] = $this->{$attributeField};
        }

        if (is_array($this->_snapshot)) {
            $primaryKeyEqual = true;
            foreach ($primaryKeys as $attributeField) {
                if (!isset($this->_snapshot[$attributeField]) || $this->_snapshot[$attributeField] !== $this->{$attributeField}) {
                    $primaryKeyEqual = false;
                }
            }

            if ($primaryKeyEqual) {
                return true;
            }
        }

        $sql = 'SELECT COUNT(*) as row_count' . ' FROM `' . $this->getSource() . '` WHERE ' . implode(' AND ',
                $conditions);
        $num = $this->getWriteConnection()->fetchOne($sql, $bind, \PDO::FETCH_ASSOC);

        return $num['row_count'] > 0;
    }

    /**
     * Generate a SQL SELECT statement for an aggregate
     *
     * @param string       $function
     * @param string       $alias
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected static function _groupResult($function, $alias, $column, $parameters, $cacheOptions)
    {
        $dependencyInjector = Di::getDefault();
        $modelsManager = $dependencyInjector->getShared('modelsManager');
        if ($parameters === null) {
            $parameters = [];
        } elseif (is_string($parameters)) {
            $parameters = [$parameters];
        }

        if (isset($parameters['group'])) {
            $columns = "$parameters[group], $function($column) AS $alias";
        } /** @noinspection DefaultValueInElseBranchInspection */ else {
            $columns = "$function($column) AS $alias";
        }

        /**
         * @var $modelsManager \ManaPHP\Mvc\Model\Manager
         */
        $builder = $modelsManager->createBuilder($parameters)
            ->columns($columns)
            ->from(get_called_class());

        $resultset = $builder->execute($cacheOptions);

        if (isset($parameters['group'])) {
            return $resultset;
        }

        return $resultset[0][$alias];
    }

    /**
     * Allows to count how many records match the specified conditions
     *
     * <code>
     *
     * //How many robots are there?
     * $number = Robots::count();
     * echo "There are ", $number, "\n";
     *
     * //How many mechanical robots are there?
     * $number = Robots::count("type='mechanical'");
     * echo "There are ", $number, " mechanical robots\n";
     *
     * </code>
     *
     * @param string|array $parameters
     * @param string       $column
     * @param int|array    $cacheOptions
     *
     * @return int|array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function count($parameters = null, $column = '*', $cacheOptions = null)
    {
        $result = self::_groupResult('COUNT', 'row_count', $column, $parameters, $cacheOptions);
        if (is_string($result)) {
            $result = (int)$result;
        }

        return $result;
    }

    /**
     * Allows to calculate a summary on a column that match the specified conditions
     *
     * <code>
     *
     * //How much are all robots?
     * $sum = Robots::sum(array('column' => 'price'));
     * echo "The total price of robots is ", $sum, "\n";
     *
     * //How much are mechanical robots?
     * $sum = Robots::sum(array("type='mechanical'", 'column' => 'price'));
     * echo "The total price of mechanical robots is  ", $sum, "\n";
     *
     * </code>
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function sum($column, $parameters = null, $cacheOptions = null)
    {
        return self::_groupResult('SUM', 'summary', $column, $parameters, $cacheOptions = null);
    }

    /**
     * Allows to get the max value of a column that match the specified conditions
     *
     * <code>
     *
     * //What is the max robot id?
     * $id = Robots::max(array('column' => 'id'));
     * echo "The max robot id is: ", $id, "\n";
     *
     * //What is the max id of mechanical robots?
     * $sum = Robots::max(array("type='mechanical'", 'column' => 'id'));
     * echo "The max robot id of mechanical robots is ", $id, "\n";
     *
     * </code>
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function max($column, $parameters = null, $cacheOptions = null)
    {
        return self::_groupResult('MAX', 'maximum', $column, $parameters, $cacheOptions);
    }

    /**
     * Allows to get the min value of a column that match the specified conditions
     *
     * <code>
     *
     * //What is the min robot id?
     * $id = Robots::min(array('column' => 'id'));
     * echo "The min robot id is: ", $id;
     *
     * //What is the min id of mechanical robots?
     * $sum = Robots::min(array("type='mechanical'", 'column' => 'id'));
     * echo "The min robot id of mechanical robots is ", $id;
     *
     * </code>
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function min($column, $parameters = null, $cacheOptions = null)
    {
        return self::_groupResult('MIN', 'minimum', $column, $parameters, $cacheOptions);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * <code>
     *
     * //What's the average price of robots?
     * $average = Robots::average(array('column' => 'price'));
     * echo "The average price is ", $average, "\n";
     *
     * //What's the average price of mechanical robots?
     * $average = Robots::average(array("type='mechanical'", 'column' => 'price'));
     * echo "The average price of mechanical robots is ", $average, "\n";
     *
     * </code>
     *
     * @param string       $column
     * @param string|array $parameters
     * @param int|array    $cacheOptions
     *
     * @return double
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function average($column, $parameters = null, $cacheOptions = null)
    {
        return (double)self::_groupResult('AVG', 'average', $column, $parameters, $cacheOptions);
    }

    /**
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     *
     * @param string $eventName
     *
     * @return void
     */
    protected function _fireEvent($eventName)
    {
        if (method_exists($this, $eventName)) {
            $this->{$eventName}();
        }

        $this->fireEvent('model:' . $eventName);
    }

    /**
     * Fires an internal event that cancels the operation
     *
     * @param string $eventName
     *
     * @return bool
     */
    protected function _fireEventCancel($eventName)
    {
        if (method_exists($this, $eventName) && $this->{$eventName}() === false) {
            return false;
        }

        if ($this->fireEvent('model:' . $eventName) === false) {
            return false;
        }

        return true;
    }

    /**
     * Sends a pre-build INSERT SQL statement to the relational database system
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected function _doLowInsert()
    {
        $columnValues = [];
        foreach ($this->modelsMetadata->getAttributes($this) as $attributeField) {
            if ($this->{$attributeField} !== null) {
                $columnValues[$attributeField] = $this->{$attributeField};
            }
        }

        if (count($columnValues) === 0) {
            throw new Exception('Unable to insert into ' . $this->getSource() . ' without data');
        }

        $connection = $this->getWriteConnection();

        $connection->insert($this->getSource(), $columnValues);
        $autoIncrementAttribute = $this->modelsMetadata->getAutoIncrementAttribute($this);
        if ($autoIncrementAttribute !== null) {
            $this->{$autoIncrementAttribute} = $connection->lastInsertId();
        }

        $this->_snapshot = $this->toArray();
    }

    /**
     * Sends a pre-build UPDATE SQL statement to the relational database system
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    protected function _doLowUpdate()
    {
        $conditions = [];
        foreach ($this->modelsMetadata->getPrimaryKeyAttributes($this) as $attributeField) {
            if (!isset($this->{$attributeField})) {
                throw new Exception('Record cannot be updated because it\'s some primary key has invalid value.');
            }

            $conditions[$attributeField] = $this->{$attributeField};
        }

        $columnValues = [];
        foreach ($this->modelsMetadata->getAttributes($this) as $attributeField) {
            if (isset($this->{$attributeField})) {
                /** @noinspection NestedPositiveIfStatementsInspection */
                if (!isset($this->_snapshot[$attributeField]) || $this->{$attributeField} !== $this->_snapshot[$attributeField]) {
                    $columnValues[$attributeField] = $this->{$attributeField};
                }
            }
        }

        if (count($columnValues) === 0) {
            return;
        }

        $this->getWriteConnection()->update($this->getSource(), $columnValues, $conditions);

        $this->_snapshot = $this->toArray();
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     *<code>
     *    //Creating a new robot
     *    $robot = new Robots();
     *    $robot->type = 'mechanical';
     *    $robot->name = 'Boy';
     *    $robot->year = 1952;
     *    $robot->save();
     *
     *    //Updating a robot name
     *    $robot = Robots::findFirst("id=100");
     *    $robot->name = "Biomass";
     *    $robot->save();
     *</code>
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function save($data = null, $whiteList = null)
    {
        if ($this->_exists()) {
            $this->update($data, $whiteList);
        } else {
            $this->create($data, $whiteList);
        }
    }

    /**
     * Inserts a model instance. If the instance already exists in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     *<code>
     *    //Creating a new robot
     *    $robot = new Robots();
     *    $robot->type = 'mechanical';
     *    $robot->name = 'Boy';
     *    $robot->year = 1952;
     *    $robot->create();
     *
     *  //Passing an array to create
     *  $robot = new Robots();
     *  $robot->create(array(
     *      'type' => 'mechanical',
     *      'name' => 'Boy',
     *      'year' => 1952
     *  ));
     *</code>
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function create($data = null, $whiteList = null)
    {
        if (is_array($data)) {
            $this->assign($data, $whiteList);
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeCreate') === false) {
            throw new Exception('Record cannot be created because it has been cancel.');
        }

        $this->_doLowInsert();
        $this->_fireEvent('afterCreate');
        $this->_fireEvent('afterSave');
    }

    /**
     * Updates a model instance. If the instance does n't exist in the persistence it will throw an exception
     * Returning true on success or false otherwise.
     *
     *<code>
     *    //Updating a robot name
     *    $robot = Robots::findFirst("id=100");
     *    $robot->name = "Biomass";
     *    $robot->update();
     *</code>
     *
     * @param array $data
     * @param array $whiteList
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function update($data = null, $whiteList = null)
    {
        if (is_array($data)) {
            $this->assign($data, $whiteList);
        }

        if ($this->_fireEventCancel('beforeSave') === false || $this->_fireEventCancel('beforeUpdate') === false) {
            throw new Exception('Record cannot be updated because it has been cancel.');
        }

        $this->_doLowUpdate();

        $this->_fireEvent('afterUpdate');
        $this->_fireEvent('afterSave');
    }

    /**
     * @param array        $columnValues
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function updateAll($columnValues, $conditions, $bind = [])
    {
        /**
         * @var $instance \ManaPHP\Mvc\Model
         */
        $instance = new static();

        return $instance->getWriteConnection()->update($instance->getSource(), $columnValues, $conditions, $bind);
    }

    /**
     * @param string|array $conditions
     * @param array        $bind
     *
     * @return int
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public static function deleteAll($conditions, $bind = [])
    {
        /**
         * @var $instance \ManaPHP\Mvc\Model
         */
        $instance = new static();

        return $instance->getWriteConnection()->delete($instance->getSource(), $conditions, $bind);
    }

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * <code>
     *$robot = Robots::findFirst("id=100");
     *$robot->delete();
     *
     *foreach (Robots::find("type = 'mechanical'") as $robot) {
     *   $robot->delete();
     *}
     * </code>
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function delete()
    {
        $writeConnection = $this->getWriteConnection();
        $primaryKeys = $this->modelsMetadata->getPrimaryKeyAttributes($this);

        if (count($primaryKeys) === 0) {
            throw new Exception('A primary key must be defined in the model in order to perform the operation');
        }

        if ($this->_fireEventCancel('beforeDelete') === false) {
            throw new Exception('Record cannot be deleted because it has been cancel.');
        }

        $conditions = [];
        foreach ($primaryKeys as $attributeField) {
            if (!isset($this->{$attributeField})) {
                throw new Exception("Cannot delete the record because the primary key attribute: '" . $attributeField . "' wasn't set");
            }

            $conditions[$attributeField] = $this->{$attributeField};
        }

        $writeConnection->delete($this->getSource(), $conditions);
        $this->_fireEvent('afterDelete');
    }

    /**
     * Returns the instance as an array representation
     *
     *<code>
     * print_r($robot->toArray());
     *</code>
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function toArray()
    {
        $data = [];

        foreach ($this->modelsMetadata->getAttributes($this) as $attributeField) {
            $data[$attributeField] = isset($this->{$attributeField}) ? $this->{$attributeField} : null;
        }

        return $data;
    }

    /**
     * Returns the internal snapshot data
     *
     * @return array
     */
    public function getSnapshotData()
    {
        return $this->_snapshot;
    }

    /**
     * Returns a list of changed values
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function getChangedFields()
    {
        $changed = [];

        foreach ($this->modelsMetadata->getAttributes($this) as $field) {
            if (!isset($this->_snapshot[$field]) || $this->{$field} !== $this->_snapshot[$field]) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param string|array $fields
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function hasChanged($fields)
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($fields as $field) {
            if (!isset($this->_snapshot[$field]) || $this->{$field} !== $this->_snapshot[$field]) {
                return true;
            }
        }

        return false;
    }
}