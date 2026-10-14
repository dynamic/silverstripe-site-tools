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
        // SCOPE_GLOBAL is what dynamic/silverstripe-site-tools#45 prescribes - its "Done when" clause is a grep
        // for it. Two things follow from it here.
        //
        // 1. Deprecation's supported-module filter cannot drop this notice: a global-scope notice is buffered
        //    with an empty backtrace, so isCalledFromSupportedCode() returns false for it, while a SCOPE_METHOD
        //    notice is dropped when the file of the *caller* - not this method's own file - sits in a supported
        //    module. Deprecation's other filter is not scope-dependent: a caller that wraps this method in
        //    withSuppressedNotice() still has the notice dropped unless it enabled showNoReplacementNotices.
        // 2. The message is exactly the text below. SCOPE_METHOD would put the *using* class's method first and
        //    append "Called from <call site>.", which is why this text names the trait and method itself. The
        //    cost is the same thing seen from the other side: no call-site attribution, so the notice cannot say
        //    which particular use a developer should remove.
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
