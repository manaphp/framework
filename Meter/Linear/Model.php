<?php
namespace ManaPHP\Meter\Linear;

/**
 * Class ManaPHP\Meter\Linear\Model
 *
 * @package linearMeter
 */
class Model extends \ManaPHP\Db\Model
{
    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $count;

    /**
     * @var int
     */
    public $created_time;

    public function getSource($context = null)
    {
        return 'manaphp_linear_meter';
    }
}
