<?php

namespace Dynamic\SiteTools\Tests\TestOnly\Model;

use Dynamic\SiteTools\Extension\DataobjectPermissionExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

/**
 * Class TestContentAuthorObject.
 */
class TestContentAuthorObject extends DataObject implements TestOnly
{
    private static $extensions = [
        DataobjectPermissionExtension::class,
    ];

    /**
     * @var string
     */
    //private static $table_name = 'TestContentAuthorObject_Test';
}
