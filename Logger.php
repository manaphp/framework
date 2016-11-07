<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/3/20
 */
namespace ManaPHP;

use ManaPHP\Logger\Exception as LoggerException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Logger
 *
 * @package logger
 */
class Logger extends Component implements LoggerInterface
{
    const LEVEL_OFF = 'OFF';
    const LEVEL_FATAL = 'FATAL';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_ALL = 'ALL';

    /**
     * @var array
     */
    protected $_s2i;

    /**
     * @var string
     */
    protected $_level = self::LEVEL_ALL;

    /**
     * @var \ManaPHP\Logger\AdapterInterface
     */
    public $adapter;

    /**
     * Logger constructor.
     *
     * @param string|array|\ManaPHP\Logger\AdapterInterface $options
     *
     * @throws \ManaPHP\Logger\Exception
     */
    public function __construct($options = [])
    {
        if (is_object($options) || is_string($options)) {
            $options = ['adapter' => $options];
        }

        if (isset($options['adapter'])) {
            $this->adapter = $options['adapter'];
        }

        if (isset($options['level'])) {
            $this->setLevel($options['level']);
        }

        $this->_s2i = array_flip([self::LEVEL_OFF, self::LEVEL_FATAL, self::LEVEL_ERROR, self::LEVEL_WARNING, self::LEVEL_INFO, self::LEVEL_DEBUG, self::LEVEL_ALL]);
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (!is_object($this->adapter)) {
            $this->adapter = $this->_dependencyInjector->getShared($this->adapter);
        }

        return $this;
    }

    /**
     * Filters the logs sent to the handlers to be greater or equals than a specific level
     *
     * @param string $level
     *
     * @return static
     * @throws \ManaPHP\Logger\Exception
     */
    public function setLevel($level)
    {
        if (!isset($this->_s2i[$level])) {
            throw new LoggerException('`:level` level is not one of `:levels`'/**m0511c3e8c2bcd64c8*/,
                ['level' => $level, 'levels' => implode(',', array_keys($this->getLevels()))]);
        }

        $this->_level = $level;

        return $this;
    }

    /**
     * Returns the current log level
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return $this->_s2i;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function log($level, $message, $context)
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $location = '';
        if (isset($traces[1])) {
            $trace = $traces[1];
            if (isset($trace['file'], $trace['line'])) {
                $location = str_replace($this->alias->get('@app'), '', str_replace('\\', '/', $trace['file'])) . ':' . $trace['line'];
            }
        }

        if (Text::contains($message, '%')) {
            $replaces = [];
            foreach ($context as $k => $v) {
                $replaces['%' . $k . '%'] = $v;
            }
            $message = strtr($message, $replaces);
        }

        $context['level'] = $level;
        $context['date'] = time();
        $context['location'] = $location;

        $eventData = ['level' => $level, 'message' => $message, 'context' => $context];
        $this->fireEvent('logger:log', $eventData);

        if ($this->_s2i[$level] > $this->_s2i[$this->_level]) {
            return $this;
        }

        $this->adapter->log($level, $message, $context);

        return $this;
    }

    /**
     * Sends/Writes a debug message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function debug($message, $context = [])
    {
        return $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Sends/Writes an info message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function info($message, $context = [])
    {
        return $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Sends/Writes a warning message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function warning($message, $context = [])
    {
        return $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Sends/Writes an error message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function error($message, $context = [])
    {
        return $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Sends/Writes a critical message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function fatal($message, $context = [])
    {
        return $this->log(self::LEVEL_FATAL, $message, $context);
    }
}