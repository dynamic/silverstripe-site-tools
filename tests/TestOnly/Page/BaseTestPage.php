<?php

namespace Dynamic\SiteTools\Test\TestOnly\Page;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

/**
 * Page class HeaderImageExtension is applied to in tests. It is the class the HeaderImageID column
 * belongs to, so it is also the class the owner walk queries on behalf of everything below it.
 *
 * @see MiddleTestPage
 * @see DeepTestPage
 */
class BaseTestPage extends SiteTree implements TestOnly
{
}
