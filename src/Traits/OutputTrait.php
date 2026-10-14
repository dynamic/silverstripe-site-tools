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
        // SCOPE_GLOBAL is required by dynamic/silverstripe-site-tools#45. What it buys, verified against
        // vendor/silverstripe/framework/src/Dev/Deprecation.php: a global-scope notice is buffered with an
        // empty backtrace, so isCalledFromSupportedCode() returns false for it and outputNotices() never
        // filters it as "called by supported-module code", whoever the caller is. The price is that the
        // "Called from <call site>." suffix Deprecation would otherwise append is not built, which is why the
        // message names the trait and method itself.
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
