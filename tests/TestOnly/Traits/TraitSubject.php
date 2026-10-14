<?php

namespace Dynamic\SiteTools\Test\TestOnly\Traits;

use Dynamic\SiteTools\Traits\OutputTrait;
use Dynamic\SiteTools\Traits\YieldTrait;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Dev\TestOnly;

/**
 * Minimal class exercising OutputTrait and YieldTrait, which no class in this module uses.
 *
 * OutputTrait::output() reaches for config(), so Configurable is required for the trait's
 * enable_output flag to resolve.
 */
class TraitSubject implements TestOnly
{
    use Configurable;
    use OutputTrait;
    use YieldTrait;
}
