<?php

namespace Dynamic\SiteTools\Traits;

use SilverStripe\Dev\Deprecation;

/**
 * Trait YieldTrait
 *
 * @package Dynamic\SiteTools\Traits
 * @deprecated 6.1.0 Will be removed without equivalent functionality to replace it in a future major release.
 */
trait YieldTrait
{
    /**
     * @param iterable $list
     * @return \Generator
     */
    public function yieldSingle($list)
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
        //
        // Deprecation::notice(), not noticeWithNoReplacment(): the latter's notices are hidden unless
        // Deprecation::enable(true) is called, and a removal notice nobody sees does not do the job #45 asks
        // for here - that the use stops before 7.0.0. The message text is the same either way.
        Deprecation::notice(
            '6.1.0',
            'YieldTrait::yieldSingle() is deprecated. Will be removed without equivalent functionality to '
            . 'replace it in a future major release.',
            Deprecation::SCOPE_GLOBAL
        );

        return $this->doYieldSingle($list);
    }

    /**
     * @param iterable $list
     * @return \Generator
     */
    public function yieldKeyVal($list)
    {
        // SCOPE_GLOBAL is what dynamic/silverstripe-site-tools#45 prescribes; see YieldTrait::yieldSingle() for
        // what global scope guarantees, what it costs, and why notice() rather than noticeWithNoReplacment().
        Deprecation::notice(
            '6.1.0',
            'YieldTrait::yieldKeyVal() is deprecated. Will be removed without equivalent functionality to '
            . 'replace it in a future major release.',
            Deprecation::SCOPE_GLOBAL
        );

        return $this->doYieldKeyVal($list);
    }

    /**
     * The two public methods above deliberately carry no return type: adding one to a trait method in a minor
     * release would fatally break any subclass that overrides it without one, and this release is meant to be
     * notice-only. They are also not generators themselves, so the notice is raised when the method is called
     * rather than on the first iteration of the returned generator. Keeping the yielding in these private
     * generators preserves the pre-existing laziness: a non-iterable $list still only fails when the generator
     * is started, not when it is created.
     *
     * @param iterable $list
     * @return \Generator
     */
    private function doYieldSingle($list): \Generator
    {
        foreach ($list as $item) {
            yield $item;
        }
    }

    /**
     * @param iterable $list
     * @return \Generator
     */
    private function doYieldKeyVal($list): \Generator
    {
        foreach ($list as $key => $val) {
            yield $key => $val;
        }
    }
}
