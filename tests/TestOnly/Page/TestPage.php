<?php

namespace Dynamic\SiteTools\Tests\TestOnly\Page;

use Dynamic\SiteTools\Extension\HeaderImageExtension;
use Page;
use SilverStripe\Dev\TestOnly;
use Dynamic\SiteTools\ORM\PreviewExtension;

/**
 * Class TestPage.
 *
 * @property string $TestPageDBField
 */
class TestPage extends Page implements TestOnly
{
    /**
     * @var array
     */
    private static $db = [
        'TestPageDBField' => 'Varchar',
    ];

    /**
     * @var string
     */
    private static $table_name = 'TestPage_Test';

    /**
     * @var array
     */
    private static $extensions = [
        PreviewExtension::class,
        HeaderImageExtension::class,
    ];
}
