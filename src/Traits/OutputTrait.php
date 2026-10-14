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
        // for it. What global scope guarantees is that the notice is never filtered out: Deprecation buffers a
        // global-scope notice with an empty backtrace, so isCalledFromSupportedCode() returns false for it and
        // outputNotices() can never drop it as "called by supported-module code", while a SCOPE_METHOD notice
        // is dropped whenever the call happens to come from inside a supported module. Scope also decides the
        // message: SCOPE_METHOD would put the *using* class's method first and append "Called from <call
        // site>.", so under global scope the message is exactly the text below - which is why it names the
        // trait and method itself. The cost is that call-site attribution, which would say which use to remove,
        // is not carried in the notice.
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
