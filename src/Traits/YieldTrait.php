<?php

namespace Dynamic\SiteTools\Traits;

/**
 * Trait YieldTrait
 * @package Dynamic\SiteTools\Traits
 */
trait YieldTrait
{
    /**
     * @param $list
     * @return \Generator
     */
    public function yieldSingle($list)
    {
        foreach ($list as $item) {
            yield $item;
        }
    }

    /**
     * @param $list
     * @return \Generator
     */
    public function yieldKeyVal($list)
    {
        foreach ($list as $key => $val) {
            yield $key => $val;
        }
    }
}
