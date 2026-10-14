<?php

namespace Dynamic\SiteTools\Test\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

/**
 * TEMPORARY PROBE - deleted again. Does declaring an abstract TestOnly page class in tests/
 * break the temp database build (the reason the previous round recorded for not writing an
 * abstract-base regression test)?
 */
abstract class AbstractProbe_Page extends SiteTree
{
}

class AbstractProbeTest extends SapphireTest
{
    protected static $uses_database = true;

    public function testProbe(): void
    {
        $this->assertTrue(true);
    }
}
