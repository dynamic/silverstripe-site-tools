<?php

namespace Dynamic\SiteTools\Test\TestOnly\Page;

use SilverStripe\Dev\TestOnly;

/**
 * Page directly below {@see BaseTestPage}. The owner walk skips it: querying
 * {@see BaseTestPage} already covers every class under it. Being skipped also means it is never
 * recorded as queried, which is what makes the class below it the interesting case.
 */
class MiddleTestPage extends BaseTestPage implements TestOnly
{
}
