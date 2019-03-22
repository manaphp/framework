<?php
namespace ManaPHP\Pool;

interface ManagerInterface
{
    /**
     * @param object $owner
     * @param object $sample
     * @param int    $size
     * @param string $type
     *
     * @return static
     */
    public function add($owner, $sample, $size = 1, $type = 'default');

    /**
     * @param object $owner
     * @param object $instance
     * @param string $type
     *
     * @return static
     */
    public function push($owner, $instance, $type = 'default');

    /**
     * @param object $owner
     * @param float  $timeout
     * @param string $type
     *
     * @return mixed
     *
     * @throws \ManaPHP\Pool\NotExistsException
     */
    public function pop($owner, $timeout = null, $type = 'default');

    /**
     * @param object $owner
     * @param string $type
     *
     * @return bool
     */
    public function exists($owner, $type = 'default');
}