<?php

namespace Dynamic\SiteTools\Traits;

use SilverStripe\Control\Director;

/**
 * Trait OutputTrait
 * @package Dynamic\SiteTools\Traits
 */
trait OutputTrait
{
    /**
     * @var
     */
    private $line_ending;

    /**
     * @var bool
     */
    private static $enable_output = true;

    /**
     * @return $this
     */
    protected function setLineEnding()
    {
        $this->line_ending = Director::is_cli() ? PHP_EOL : '<br />';

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getLineEnding()
    {
        if (!$this->line_ending) {
            $this->setLineEnding();
        }

        return $this->line_ending;
    }

    /**
     * @param $string
     */
    public function output($string)
    {
        if ($this->config()->get('enable_output')) {
            echo $string . $this->getLineEnding();
        }
    }
}
