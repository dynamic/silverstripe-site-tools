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
    public function yieldSingle($list): \Generator
    {
        // SCOPE_GLOBAL is deliberate: this is a trait method, so it is always reached through the class using
        // it, and SilverStripe 6 hides a method-scoped notice raised by supported-module code. Global scope
        // keeps the notice visible to the installing project, which is the only code able to stop using it.
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
    public function yieldKeyVal($list): \Generator
    {
        // SCOPE_GLOBAL is deliberate: this is a trait method, so it is always reached through the class using
        // it, and SilverStripe 6 hides a method-scoped notice raised by supported-module code. Global scope
        // keeps the notice visible to the installing project, which is the only code able to stop using it.
        Deprecation::notice(
            '6.1.0',
            'YieldTrait::yieldKeyVal() is deprecated. Will be removed without equivalent functionality to '
            . 'replace it in a future major release.',
            Deprecation::SCOPE_GLOBAL
        );

        return $this->doYieldKeyVal($list);
    }

    /**
     * These two public methods are deliberately not generators themselves, so the notice above is raised when
     * the method is called rather than on the first iteration of the returned generator. The yielding stays in
     * these private generators, which preserves the pre-existing laziness: a non-iterable $list still only fails
     * when the generator is started, not when it is created.
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
