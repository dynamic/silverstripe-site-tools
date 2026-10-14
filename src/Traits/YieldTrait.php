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
        // SCOPE_GLOBAL is what dynamic/silverstripe-site-tools#45 prescribes; OutputTrait::output() carries the
        // full note on what scope does to the supported-module filter and to the message text.
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
        // SCOPE_GLOBAL is what dynamic/silverstripe-site-tools#45 prescribes; see OutputTrait::output() for
        // what scope does here (supported-module filtering, message text, and the withSuppressedNotice case),
        // and see YieldTrait::yieldSingle() for why notice() rather than noticeWithNoReplacment().
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
