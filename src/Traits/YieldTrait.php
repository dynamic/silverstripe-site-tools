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
        // SCOPE_GLOBAL is required by dynamic/silverstripe-site-tools#45. What it buys, verified against
        // vendor/silverstripe/framework/src/Dev/Deprecation.php: a global-scope notice is buffered with an
        // empty backtrace, so isCalledFromSupportedCode() returns false for it and outputNotices() never
        // filters it as "called by supported-module code", whoever the caller is. The price is that the
        // "Called from <call site>." suffix Deprecation would otherwise append is not built, which is why the
        // message names the trait and method itself.
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
        // SCOPE_GLOBAL is required by dynamic/silverstripe-site-tools#45; see YieldTrait::yieldSingle() for
        // what global scope buys and what it costs.
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
