<?php

namespace ManaPHP;

/**
 * Class ManaPHP\Exception
 *
 * @package exception
 */
class Exception extends \Exception
{
    /**
     * @var array
     */
    protected $_bind = [];

    /**
     * @var array
     */
    protected $_json;

    /**
     * Exception constructor.
     *
     * @param string|array $message
     * @param int          $code
     * @param \Exception   $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if (is_array($message)) {
            if (substr_count($message[0], '%') + 1 >= ($count = count($message)) && isset($message[$count - 1])) {
                /** @noinspection ArgumentUnpackingCanBeUsedInspection */
                $message = call_user_func_array('sprintf', $message);
            } else {
                $this->_bind = $message;
                $message = $message[0];
                unset($this->_bind[0]);

                if (!isset($this->_bind['last_error_message'])) {
                    $this->_bind['last_error_message'] = error_get_last()['message'];
                }

                $tr = [];
                /** @noinspection ForeachSourceInspection */
                foreach ($this->_bind as $k => $v) {
                    $tr[':' . $k] = $v;
                }

                $message = strtr($message, $tr);
            }
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 500;
    }

    /**
     * @return string
     */
    public function getStatusText()
    {
        $code = $this->getStatusCode();

        $codeTexts = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested range unsatisfiable',
            417 => 'Expectation failed',
            418 => 'I\'m a teapot',
            421 => 'Misdirected Request',
            422 => 'Unprocessable entity',
            423 => 'Locked',
            424 => 'Method failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway or Proxy Error',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            507 => 'Insufficient storage',
            508 => 'Loop Detected',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        ];

        return isset($codeTexts[$code]) ? $codeTexts[$code] : 'App Error';
    }

    /**
     * @param array|string $data
     *
     * @return static
     */
    public function setJson($data)
    {
        if (is_array($data)) {
            $this->_json = $data;
        } elseif (is_string($data)) {
            $this->_json = ['code' => 1, 'message' => $data];
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getJson()
    {
        return $this->_json ? $this->_json : ['code' => 1, 'message' => $this->getMessage()];
    }

    /**
     * @return array
     */
    public function getBind()
    {
        return $this->_bind;
    }
}
