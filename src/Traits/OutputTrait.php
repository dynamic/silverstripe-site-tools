<?php

namespace Dynamic\SiteTools\Traits;

use SilverStripe\Control\Director;
use SilverStripe\Dev\Deprecation;

/**
 * Trait OutputTrait
 *
 * @package Dynamic\SiteTools\Traits
 * @deprecated 6.1.0 Will be removed without equivalent functionality to replace it in a future major release.
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
        // SCOPE_GLOBAL is deliberate: this is a trait method, so it is always reached through the class using
        // it, and SilverStripe 6 hides a method-scoped notice raised by supported-module code. Global scope
        // keeps it visible to the installing project, which is the only code able to stop using it.
        Deprecation::notice(
            '6.1.0',
            'OutputTrait::output() is deprecated. Will be removed without equivalent functionality to '
            . 'replace it in a future major release.',
            Deprecation::SCOPE_GLOBAL
        );

        if ($this->config()->get('enable_output')) {
            echo $string . $this->getLineEnding();
        }
    }
}
