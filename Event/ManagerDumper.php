<?php
declare(strict_types=1);

namespace ManaPHP\Event;

use ManaPHP\Dumping\Dumper;

class ManagerDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        return ['*events'  => array_keys($data['events']),
                '*peekers' => array_keys($data['peekers'])];
    }
}