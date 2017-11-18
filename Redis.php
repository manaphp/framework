<?php
namespace ManaPHP;

use ManaPHP\Redis\Exception as RedisException;

class Redis extends \Redis
{
    /**
     * @var string
     */
    protected $_host;

    /**
     * @var int
     */
    protected $_port;

    /**
     * @var float
     */
    protected $_timeout;

    /**
     * @var int
     */
    protected $_retry_interval;

    /**
     * @var string
     */
    protected $_auth;

    /**
     * @var int
     */
    protected $_db;

    /**
     * Redis constructor.
     *
     * @param array|string $options
     *
     * @throws \ManaPHP\Redis\Exception
     */
    public function __construct($options)
    {
        if (is_string($options)) {
            $url = $options;

            $parts = parse_url($options);

            $options = [];

            if ($parts['scheme'] !== 'redis') {
                throw new RedisException('`:url` is invalid, `:scheme` scheme is not recognized', ['url' => $url, 'scheme' => $parts['scheme']]);
            }

            if (isset($parts['host'])) {
                $options['host'] = $parts['host'];
            }

            if (isset($parts['port'])) {
                $options['port'] = $parts['port'];
            }

            if (isset($parts['path']) && $parts['path'] !== '/') {
                $path = trim($parts['path'], '/');
                if (!is_numeric($path)) {
                    throw new RedisException('`:url` url is invalid, `:db` db is not integer', ['url' => $url, 'db' => $path]);
                }
                $options['db'] = $path;
            }

            if (isset($parts['query'])) {
                parse_str($parts['query'], $parts2);
                if (isset($parts2['timeout'])) {
                    $options['timeout'] = $parts2['timeout'];
                }

                if (isset($parts2['retry_interval'])) {
                    $options['retry_interval'] = $parts2['retry_interval'];
                }

                if (isset($parts2['auth'])) {
                    $options['auth'] = $parts2['auth'];
                }
            }
        } elseif (is_object($options)) {
            $options = (array)$options;
        }

        $this->_host = isset($options['host']) ? $options['host'] : 'localhost';
        $this->_port = isset($options['port']) ? (int)$options['port'] : 6379;
        $this->_timeout = isset($options['timeout']) ? (float)$options['timeout'] : 0.0;
        $this->_retry_interval = isset($options['retry_interval']) ? (int)$options['retry_interval'] : 0;
        $this->_auth = isset($options['auth']) ? $options['auth'] : '';
        $this->_db = isset($options['db']) ? (int)$options['db'] : 0;

        parent::__construct();

        $this->_connect();
    }

    /**
     * @throws \ManaPHP\Redis\Exception
     */
    protected function _connect()
    {
        $this->connect($this->_host, $this->_port, $this->_timeout, null, $this->_retry_interval);

        if ($this->_auth !== '') {
            if (!$this->auth($this->_auth)) {
                throw new RedisException('`:auth` auth is wrong.', ['auth' => $this->_auth]);
            }
        }

        if ($this->_db !== 0) {
            if (!$this->select($this->_db)) {
                throw new RedisException('select `:db` db failed', ['db' => $this->_db]);
            }
        }
    }

    /**
     * @return static
     * @throws \ManaPHP\Redis\Exception
     */
    public function reconnect()
    {
        $this->close();
        $this->_connect();

        return $this;
    }

    /**
     * @param string $key
     *
     * @return array|string
     */
    public function dump($key = null)
    {
        if ($key === null) {
            return get_object_vars($this);
        } else {
            return parent::dump($key);
        }
    }
}