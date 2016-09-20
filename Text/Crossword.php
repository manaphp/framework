<?php
namespace ManaPHP\Text;

class Crossword
{
    /**
     * @param string|array $words
     * @param string       $word
     *
     * @return string|false
     */
    public function guess($words, $word)
    {
        if (is_string($words)) {
            if (strpos($words, ',') !== false) {
                $words = explode(',', $words);
            } else {
                $words = [$words];
            }
        }

        $word = strtolower($word);

        /** @noinspection ForeachSourceInspection */
        foreach ($words as $k => $v) {
            if (strspn($word, strtolower($v)) !== strlen($word)) {
                unset($words[$k]);
            }
        }

        if (count($words) === 0) {
            return false;
        } elseif (count($words) === 1) {
            return array_values($words)[0];
        } else {
            return false;
        }
    }
}