<?php

declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use function count;
use function is_float;
use function is_int;
use function is_string;

class Histogram
{
    protected array $les = [];
    public array $buckets = [];
    public float $sum = 0;
    public int $count = 0;

    public function __construct(array $buckets)
    {
        if (count($buckets) === 2 && is_int($buckets[1])) {
            list($start, $count) = $buckets;

            for ($i = 0; $i < $count; $i++) {
                $le = $start * (2 ** $i);
                $this->les[] = $le;
                $this->buckets[is_float($le) ? (string)$le : $le] = 0;
            }
        } else {
            foreach ($buckets as $le) {
                $this->les[] = is_string($le) ? (float)$le : $le;
                $this->buckets[is_float($le) ? (string)$le : $le] = 0;
            }
        }
    }

    public function update(float $v): void
    {
        foreach ($this->les as $le) {
            if ($v <= $le) {
                $this->buckets[is_float($le) ? (string)$le : $le]++;
            }
        }

        $this->sum += $v;
        $this->count++;
    }
}
