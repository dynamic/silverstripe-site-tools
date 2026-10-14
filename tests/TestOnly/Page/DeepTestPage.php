<?php

namespace Dynamic\SiteTools\Test\TestOnly\Page;

use SilverStripe\Dev\TestOnly;

/**
 * Page two levels below {@see BaseTestPage}. Its immediate parent, {@see MiddleTestPage}, is a
 * class the owner walk skips rather than queries, so a walk that compares a class only against the
 * class it extends would query this table for owners it already has.
 */
class DeepTestPage extends MiddleTestPage implements TestOnly
{
}
